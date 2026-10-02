<?php

namespace Tests\Feature;

use App\Models\{Announcement, Subject, Teacher};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_with_records_and_hide_unpublished_announcements(): void
    {
        $subject = Subject::create(['name' => 'Mathematics', 'price' => 80, 'is_active' => true]);
        $teacher = Teacher::create(['name' => 'Public Teacher', 'email' => 'teacher@example.test', 'is_active' => true]);
        $teacher->subjects()->attach($subject);
        Announcement::create(['title' => 'Published update', 'content' => 'Term begins soon.', 'published_at' => now()->subMinute()]);
        Announcement::create(['title' => 'Hidden draft', 'content' => 'Draft content']);
        Announcement::create(['title' => 'Future update', 'content' => 'Future content', 'published_at' => now()->addDay()]);
        $this->get('/')->assertOk()->assertSee('Published update')->assertDontSee('Hidden draft')->assertDontSee('Future update');
        $this->get('/teachers')->assertOk()->assertSee('Public Teacher')->assertSee('Mathematics');
        $this->get('/announcements')->assertOk()->assertSee('Published update')->assertDontSee('Hidden draft')->assertDontSee('Future update');
        $this->get('/feedback')->assertOk()->assertSee('Submit feedback');
    }

    public function test_feedback_accepts_an_empty_phone_and_rejects_spam(): void
    {
        $data = ['name' => 'Visitor', 'email' => 'visitor@example.test', 'phone' => '', 'message' => 'Thank you for your lessons.', 'website' => ''];
        $this->post('/feedback', $data)->assertRedirect(route('public.feedback'));
        $this->assertDatabaseHas('feedback', ['email' => $data['email'], 'phone' => null, 'status' => 'new']);
        $this->post('/feedback', array_merge($data, ['website' => 'spam.example']))->assertSessionHasErrors('website');
        $this->assertDatabaseCount('feedback', 1);
    }
}
