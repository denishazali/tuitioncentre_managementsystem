<?php

namespace App\Http\Controllers\Admin;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubjectController extends AdminController
{
    protected string $module = 'subjects';

    protected array $columns = [
        'name' => 'Subject',
        'price' => 'Price (RM)',
        'is_active' => 'Active',
    ];

    public function index()
    {
        return $this->listing(Subject::orderBy('name'));
    }

    public function create()
    {
        return $this->form(new Subject(['is_active' => true]));
    }

    public function store(Request $request)
    {
        Subject::create($this->validated($request));

        return $this->saved('Subject created.');
    }

    public function edit(Subject $subject)
    {
        return $this->form($subject);
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $this->validated($request, $subject);

        if (! $data['is_active'] && $subject->timetables()->exists()) {
            throw ValidationException::withMessages([
                'is_active' => 'Remove or reassign this subject’s timetable slots before deactivating it.',
            ]);
        }

        $subject->update($data);

        return $this->saved('Subject updated.');
    }

    public function destroy(Subject $subject)
    {
        $inUse = $subject->timetables()->exists()
            || $subject->enrollments()->exists()
            || $subject->quizzes()->exists()
            || DB::table('package_subject')
                ->where('subject_id', $subject->id)->exists()
            || DB::table('exam_results')
                ->where('subject_id', $subject->id)->exists();

        return $this->deleteRecord($subject, $inUse);
    }

    private function validated(
        Request $request,
        ?Subject $subject = null
    ): array {
        return $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:100', 
                Rule::unique('subjects', 'name')
                    ->ignore($subject?->id),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
