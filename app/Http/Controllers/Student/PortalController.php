<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $student = $request->user()->studentProfile;

        return view('student.dashboard', [
            'student' => $student,
            'subjectCount' => $this->approvedSubjects($student)->count(),
            'classCount' => $this->timetableQuery($student)->count(),
        ]);
    }

    public function subjects(Request $request)
    {
        $student = $request->user()->studentProfile;

        return view('student.subjects.index', [
            'student' => $student,
            'subjects' => $this->approvedSubjects($student)
            ->orderBy('name')
            ->get()
        ]);
    }

    public function timetable(Request $request)
    {
        $student = $request->user()->studentProfile;

        return view('student.timetable.index', [
            'student' => $student,
            'subjectCount' => $this->approvedSubjects($student)->count(),
            'slots' => $this->timetableQuery($student)->get(),
        ]);
        
    }

    private function approvedSubjects(?Student $student): Builder
    {
        // Each subject appears once, even across multiple enrollments.
        return Subject::query()
            ->whereHas('enrollments', function (Builder $query) use ($student) {
                $query->where('student_id', $student?->id ?? 0)
                    ->where('status', 'approved');
        });
    }

    private function timetableQuery(?Student $student): Builder
    {
        /** @var Builder $query */
        $query = Timetable::query()
            ->where('form_level', $student?->getAttribute('form_level') ?? 0)
            ->whereIn(
                'subject_id',
                $this->approvedSubjects($student)->select('subject.id')
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

        return $query;
    }
}