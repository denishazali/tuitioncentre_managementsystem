<?php

namespace  App\Services;

use App\Models\Student;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Builder;

class StudentLearningService
{
    public function approvedSubjects(?Student $student): Builder
    {
        return Subject::query()
        ->whereHas('enrollments', function (Builder $query) use ($student) {
            $query->where('student_id', $student->id ?? 0)
                ->where('status', 'approved');
        });
    }

    public function timetableQuery(?Student $student): Builder
    {
        return Timetable::query()
        ->where('form_level', $student?->form_level ?? 0)
        ->whereIn(
            'subject_id',
            $this->approvedSubjects($student)->select('subjects.id')
        )
        ->whereHas('subject', function (Builder $query) {
            $query->where('is_active', true);
        })
        ->whereHas('teacher', function (Builder $query) {
            $query->where('is_active', true);
        })
        ->with(['subject', 'teacher'])
        ->orderByRaw("
            CASE day
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
                ELSE 8
            END
        ")
        ->orderBy('start_time')
        ->orderBy('id');
    }
}