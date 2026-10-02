<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Feedback;
use App\Models\Teacher;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    /**
     * Display the public homepage
     */
    public function home(): View
    {
        $latestAnnouncements = Announcement::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('public.home', [
            'latestAnnouncements' => $latestAnnouncements,
        ]);
    }

    /**
     * Display active teachers and their subjects
     */
    public function teachers(): View
    {
        $teachers = Teacher::query()
            ->where('is_active', true)
            ->with([
                'subjects' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->paginate(12);

        return view('public.teacher', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Display published announcements.
     */
    public function announcements(): View
    {
        $announcements = Announcement::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->latest('published_at')
            ->paginate(9);

        return view('public.announcement', [
            'announcements' => $announcements,
        ]);
    }

    /**
     * Display the feedback form and approved public feedback
     */
    public function feedback(): View
    {
        $feedbackEntries = Feedback::query()
            ->whereIn('status', ['reviewed', 'resolved'])
            ->latest()
            ->paginate(8);

        return view('public.feedback', [
            'feedbackEntries' => $feedbackEntries,
        ]);
    }
}
