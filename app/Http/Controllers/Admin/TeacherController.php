<?php

namespace App\Http\Controllers\Admin;

use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherController extends AdminController
{
    protected string $module = 'teachers';

    protected array $columns = [
        'name' => 'Teacher',
        'email' => 'Email',
        'is_active' => 'Active',
    ];

    public function index()
    {
        return $this->listing(Teacher::orderBy('name'));
    }

    public function create()
    {
        return $this->teacherForm(
            new Teacher(['is_active' => true])
        );
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $subjectIds = (array) ($data['subjects'] ?? []);
        unset($data['subjects']);

        DB::transaction(function () use ($data, $subjectIds) {
            $teacher = Teacher::create($data);
            $teacher->subjects()->sync($subjectIds);
        });

        return $this->saved('Teacher created.');
    }

    public function edit(Teacher $teacher)
    {
        return $this->teacherForm($teacher);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $this->validated($request, $teacher);
        $subjectIds = (array) ($data['subjects'] ?? []);
        unset($data['subjects']);

        $scheduledSubjectIds = $teacher->timetables()
            ->pluck('subject_id')
            ->all();

        if (
            $scheduledSubjectIds
            && (
                ! $data['is_active']
                || array_diff($scheduledSubjectIds, $subjectIds)
            )
        ) {
            throw ValidationException::withMessages([
                'subjects' => 'Reassign or remove existing timetable slots before deactivating this teacher or removing their scheduled subjects.',
            ]);
        }

        DB::transaction(function () use (
            $teacher,
            $data,
            $subjectIds
        ) {
            $teacher->update($data);
            $teacher->subjects()->sync($subjectIds);
        });

        return $this->saved('Teacher updated.');
    }

    public function destroy(Teacher $teacher)
    {
        return $this->deleteRecord(
            $teacher,
            $teacher->timetables()->exists()
        );
    }

    private function teacherForm(Teacher $teacher)
    {
        return $this->form($teacher, [
            'subjectOptions' => Subject::orderBy('name')
                ->pluck('name', 'id'),
            'selectedSubjects' => $teacher->exists
                ? $teacher->subjects()->pluck('subjects.id')->all()
                : [],
        ]);
    }

    private function validated(
        Request $request,
        ?Teacher $teacher = null
    ): array {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('teachers', 'email')
                    ->ignore($teacher?->id),
            ],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'background' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'subjects' => ['nullable', 'array'],
            'subjects.*' => [
                'integer',
                'distinct',
                Rule::exists('subjects', 'id'),
            ],
        ]);
    }
}