<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentLearningService;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __construct(
        private StudentLearningService $learning
    ) {
    }

    public function dashboard(Request $request)
    {
        $student = $request->user()->studentProfile;

        return view('student.dashboard', [
            'student' => $student,
            'subjectCount' => $this->learning
                ->approvedSubjects($student)
                ->count(),
            'classCount' => $this->learning 
                ->timetableQuery($student)
                ->count(),
        ]);
    }

    public function subjects(Request $request)
    {
        $student = $request->user()->studentProfile;

        return view('student.subjects.index', [
            'student' => $student,
            'subjects' => $this->learning
                ->approvedSubjects($student)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function timetable (Request $request)
    {
        $student = $request->user()->studentProfile;

        return view('student.timetable.index', [
            'student' => $student,
            'subjectCount' => $this->learning
                ->approvedSubjects($student)
                ->count(),
            'slots' => $this->learning
                ->timetableQuery($student)
                ->get(),
        ]);
    }
}