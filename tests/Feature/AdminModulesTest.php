<?php

namespace Tests\Feature;

use App\Models\{Announcement, Subject, Teacher, Timetable, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'account_status' => 'active']));
    }

    private function teachingPair(): array
    {
        $subject = Subject::create(['name' => 'Mathematics', 'price' => 80, 'is_active' => true]);
        $teacher = Teacher::create(['name' => 'Teacher One', 'email' => 'teacher@example.test', 'is_active' => true]);
        $teacher->subjects()->attach($subject);
        return [$subject, $teacher];
    }

    public function test_all_admin_lists_create_and_edit_forms_render(): void
    {
        [$subject, $teacher] = $this->teachingPair();
        $announcement = Announcement::create(['title' => 'Term news', 'content' => 'Welcome back', 'published_at' => now()]);
        $user = User::factory()->create();
        $slot = Timetable::create([
            'day' => 'Monday', 'start_time' => '09:00', 'duration_minutes' => 60,
            'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'form_level' => 1,
        ]);
        $this->get('/admin/dashboard')->assertOk()->assertSee('Admin dashboard');
        foreach (['subjects' => $subject, 'teachers' => $teacher, 'announcements' => $announcement, 'users' => $user, 'timetables' => $slot] as $module => $record) {
            $this->get('/admin/'.$module)->assertOk()->assertSee('Edit');
            $this->get('/admin/'.$module.'/create')->assertOk()->assertSee('Save');
            $this->get('/admin/'.$module.'/'.$record->id.'/edit')->assertOk()->assertSee('Save');
        }
        $this->get('/admin/teachers/'.$teacher->id.'/edit')
            ->assertSee('name="subjects[]"', false)->assertSee('Mathematics');
    }

    public function test_subject_and_teacher_crud_and_assignments(): void
    {
        $this->post('/admin/subjects', ['name' => 'Science', 'price' => 90, 'is_active' => 1])
            ->assertRedirect(route('admin.subjects.index'));
        $subject = Subject::firstOrFail();
        $this->put('/admin/subjects/'.$subject->id, ['name' => 'Science', 'price' => 100, 'is_active' => 1])
            ->assertRedirect(route('admin.subjects.index'));
        $this->assertEquals(100, $subject->fresh()->price);
        $data = ['name' => 'Teacher', 'email' => 'new@example.test', 'background' => 'Experienced educator', 'is_active' => 1, 'subjects' => [$subject->id]];
        $this->post('/admin/teachers', $data)->assertRedirect(route('admin.teachers.index'));
        $teacher = Teacher::firstOrFail();
        $this->assertDatabaseHas('subject_teacher', ['teacher_id' => $teacher->id, 'subject_id' => $subject->id]);
        $this->put('/admin/teachers/'.$teacher->id, array_merge($data, ['subjects' => '']))
            ->assertRedirect(route('admin.teachers.index'));
        $this->assertDatabaseCount('subject_teacher', 0);
        $this->delete('/admin/teachers/'.$teacher->id)->assertRedirect(route('admin.teachers.index'));
        $this->delete('/admin/subjects/'.$subject->id)->assertRedirect(route('admin.subjects.index'));
        $this->assertDatabaseCount('teachers', 0);
        $this->assertDatabaseCount('subjects', 0);
    }

    public function test_announcements_and_users_can_be_created_updated_and_deleted(): void
    {
        $data = ['title' => 'News', 'content' => 'A new term begins.', 'published_at' => now()->format('Y-m-d\TH:i')];
        $this->post('/admin/announcements', $data)->assertRedirect(route('admin.announcements.index'));
        $announcement = Announcement::firstOrFail();
        $this->put('/admin/announcements/'.$announcement->id, array_merge($data, ['title' => 'Updated news']))
            ->assertRedirect(route('admin.announcements.index'));
        $this->assertSame('Updated news', $announcement->fresh()->title);
        $this->delete('/admin/announcements/'.$announcement->id)->assertRedirect(route('admin.announcements.index'));
        $data = ['name' => 'New User', 'email' => 'new@example.test', 'role' => 'parent', 'account_status' => 'active', 'password' => 'password123', 'password_confirmation' => 'password123'];
        $this->post('/admin/users', $data)->assertRedirect(route('admin.users.index'));
        $user = User::where('email', $data['email'])->firstOrFail();
        $hash = $user->password;
        $this->assertTrue(Hash::check('password123', $hash));
        $this->put('/admin/users/'.$user->id, array_merge($data, ['name' => 'Updated User', 'password' => '', 'password_confirmation' => '']))
            ->assertRedirect(route('admin.users.index'));
        $this->assertSame($hash, $user->fresh()->password);
        $this->delete('/admin/users/'.$user->id)->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('announcements', 0);
    }

    public function test_timetables_require_assignments_and_reject_conflicts_and_protect_dependencies(): void
    {
        $this->get('/admin/timetables/create')->assertRedirect(route('admin.timetables.index'));
        [$subject, $teacher] = $this->teachingPair();
        $data = ['day' => 'Monday', 'start_time' => '09:00', 'duration_minutes' => 60, 'subject_id' => $subject->id, 'teacher_id' => $teacher->id, 'form_level' => 1];
        $this->post('/admin/timetables', $data)->assertRedirect(route('admin.timetables.index'));
        $slot = Timetable::firstOrFail();
        $this->post('/admin/timetables', array_merge($data, ['start_time' => '09:30']))->assertSessionHasErrors('start_time');
        $this->post('/admin/timetables', array_merge($data, ['start_time' => '23:30']))->assertSessionHasErrors('duration_minutes');
        $other = Teacher::create(['name' => 'Other', 'email' => 'other@example.test', 'is_active' => true]);
        $this->post('/admin/timetables', array_merge($data, ['teacher_id' => $other->id]))->assertSessionHasErrors('teacher_id');
        $this->delete('/admin/subjects/'.$subject->id)->assertSessionHasErrors('record');
        $this->delete('/admin/teachers/'.$teacher->id)->assertSessionHasErrors('record');
        $this->put('/admin/teachers/'.$teacher->id, ['name' => $teacher->name, 'email' => $teacher->email, 'is_active' => 1, 'subjects' => []])->assertSessionHasErrors('subjects');
        $this->put('/admin/timetables/'.$slot->id, $data)->assertRedirect(route('admin.timetables.index'));
        $this->post('/admin/timetables', array_merge($data, ['start_time' => '10:00']))->assertRedirect(route('admin.timetables.index'));
        $this->delete('/admin/timetables/'.$slot->id)->assertRedirect(route('admin.timetables.index'));
        $this->assertDatabaseMissing('timetables', ['id' => $slot->id]);
    }

    public function test_admin_cannot_delete_or_deactivate_their_own_account(): void
    {
        $user = auth()->user();
        $this->delete('/admin/users/'.$user->id)->assertSessionHasErrors('record');
        $this->put('/admin/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => 'parent', 'account_status' => 'inactive'])->assertSessionHasErrors('role');
        $this->assertSame('admin', $user->fresh()->role);
    }
}
