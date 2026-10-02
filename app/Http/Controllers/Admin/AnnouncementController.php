<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends AdminController
{
    protected string $module = 'announcements';

    protected array $columns = [
        'title' => 'Title',
        'published_at' => 'Publication date',
        'created_at' => 'Created',
    ];

    public function index()
    {
        return $this->listing(Announcement::latest());
    }

    public function create()
    {
        return $this->form(new Announcement());
    }

    public function store(Request $request)
    {
        Announcement::create($this->validated($request));

        return $this->saved('Announcement created.');
    }

    public function edit(Announcement $announcement)
    {
        return $this->form($announcement);
    }

    public function update(Request $request, Announcement $announcement) 
    {
        $announcement->update($this->validated($request));

        return $this->saved('Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        return $this->deleteRecord($announcement);
    }

    private function validated(Request $request): array 
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date_format:Y-m-d\TH:i',],
        ]);
    }
}
