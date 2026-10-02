<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'counts' => [
                'subjects' => Subject::count(),
                'teachers' => Teacher::count(),
                'announcements' => Announcement::count(),
                'users' => User::count(),
                'timetables' => Timetable::count(),
            ],
            'pendingUsers' => User::where(
                'account_status',
                'pending'
            )->count(),
        ]);
    }
}