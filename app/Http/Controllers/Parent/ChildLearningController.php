<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentLearningService;
use Illuminate\Http\Request;

class ChildLearningController extends Controller
{
    public function __construct(
        private StudentLearningService $learning
    ) {
    }

    public function subjects(Request $request, int $child)
    {
        $ownedChild = $this->childForParent($request, $child);

        return view('parent.children.subjects', [
            'child' => $ownedChild,
            'subjects' => $this->learning
                ->approvedSubjects($ownedChild)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function timetable(Request $request, int $child)
    {
        $ownedChild = $this->childForParent($request, $child);

        return view('parent.children.timetable', [
            'child' => $ownedChild,
            'subjectCount' => $this->learning
                ->approvedSubjects($ownedChild)
                ->count(),
            'slots' => $this->learning
                ->timetableQuery($ownedChild)
                ->get(),
        ]);
    }

    private function childForParent(
        Request $request,
        int $childId
    ): Student {
        return $request->user()
            ->children()
            ->findOrFail($childId);
    }
}