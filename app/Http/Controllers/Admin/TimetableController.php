<?php

namespace App\Http\Controllers\Admin;

use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TimetableController extends AdminController
{
    protected string $module = 'timetables';

    protected array $columns = [
        'day' => 'Day',
        'start_time' => 'Starts',
        'duration_minutes' => 'Minutes',
        'subject.name' => 'Subject',
        'teacher.name' => 'Teacher',
        'form_level' => 'Form',
    ];

    private const DAYS = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
        'Sunday',
    ];

    public function index()
    {
        return $this->listing(
            Timetable::with(['subject', 'teacher'])
                ->orderByRaw(
                    "CASE day
                        WHEN 'Monday' THEN 1
                        WHEN 'Tuesday' THEN 2
                        WHEN 'Wednesday' THEN 3
                        WHEN 'Thursday' THEN 4
                        WHEN 'Friday' THEN 5
                        WHEN 'Saturday' THEN 6
                        WHEN 'Sunday' THEN 7
                    END"
                )
                ->orderBy('start_time')
        );
    }

    public function create()
    {
        $available = Teacher::where('is_active', true)
            ->whereHas('subjects', function ($query) {
                $query->where('subjects.is_active', true);
            })
            ->exists();

        if (! $available) {
            return to_route('admin.timetables.index')
                ->withErrors([
                    'record' => 'Create an active subject and assign it to an active teacher before creating a timetable.',
                ]);
        }

        return $this->timetableForm(new Timetable([
            'duration_minutes' => 60,
            'form_level' => 1,
        ]));
    }

    public function store(Request $request)
    {
        Timetable::create($this->validated($request));

        return $this->saved('Timetable slot created.');
    }

    public function edit(Timetable $timetable)
    {
        return $this->timetableForm($timetable);
    }

    public function update(
        Request $request,
        Timetable $timetable
    ) {
        $timetable->update(
            $this->validated($request, $timetable)
        );

        return $this->saved('Timetable slot updated.');
    }

    public function destroy(Timetable $timetable)
    {
        return $this->deleteRecord($timetable);
    }

    private function timetableForm(Timetable $timetable)
    {
        $teachers = Teacher::where('is_active', true)
            ->with(['subjects' => function ($query) {
                $query->where('subjects.is_active', true);
            }])
            ->whereHas('subjects', function ($query) {
                $query->where('subjects.is_active', true);
            })
            ->orderBy('name')
            ->get();

        $teacherOptions = $teachers->mapWithKeys(
            fn (Teacher $teacher) => [
                $teacher->id => $teacher->name
                    . ' — '
                    . $teacher->subjects->pluck('name')->join(', '),
            ]
        );

        return $this->form($timetable, [
            'dayOptions' => array_combine(self::DAYS, self::DAYS),
            'subjectOptions' => Subject::where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),
            'teacherOptions' => $teacherOptions,
        ]);
    }

    private function validated(
        Request $request,
        ?Timetable $timetable = null
    ): array {
        $data = $request->validate([
            'day' => ['required', Rule::in(self::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:1440',
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')
                    ->where('is_active', true),
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teachers', 'id')
                    ->where('is_active', true),
            ],
            'form_level' => ['required', 'integer', 'between:1,5'],
        ]);

        $teacher = Teacher::findOrFail($data['teacher_id']);

        if (! $teacher->subjects()
            ->where('subjects.id', $data['subject_id'])
            ->exists()) {
            throw ValidationException::withMessages([
                'teacher_id' => 'Choose a teacher assigned to the selected subject.',
            ]);
        }

        $start = $this->minutes($data['start_time']);
        $end = $start + (int) $data['duration_minutes'];

        if ($end > 1440) {
            throw ValidationException::withMessages([
                'duration_minutes' => 'A class must finish by midnight on the selected day.',
            ]);
        }

        $query = Timetable::where('day', $data['day'])
            ->where(function ($query) use ($data) {
                $query->where('teacher_id', $data['teacher_id'])
                    ->orWhere('form_level', $data['form_level']);
            });

        if ($timetable) {
            $query->where('id', '!=', $timetable->id);
        }

        foreach ($query->get() as $slot) {
            $existingStart = $this->minutes($slot->start_time);
            $existingEnd = $existingStart
                + (int) $slot->duration_minutes;

            if ($start < $existingEnd && $end > $existingStart) {
                throw ValidationException::withMessages([
                    'start_time' => 'This slot overlaps another class for the teacher or form level.',
                ]);
            }
        }

        return $data;
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }
}