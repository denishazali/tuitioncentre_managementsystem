<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentLinkRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentChildrenTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $role = 'parent'): User
    {
        return User::factory()->create(['role' => $role, 'account_status' => 'active']);
    }

    private function child(User $parent): Student
    {
        $user = $this->account('student');

        return Student::create([
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'name' => 'Child '.$user->id,
            'ic' => 'IC-'.$user->id,
            'date_of_birth' => '2012-01-01',
            'form_level' => 2,
            'school_name' => 'Test School',
        ]);
    }

    private function linkRequest(User $parent, Student $child): StudentLinkRequest
    {
        return StudentLinkRequest::create([
            'parent_id' => $parent->id,
            'student_name' => $child->name,
            'student_ic' => str_replace('-', '', $child->ic),
            'status' => 'pending',
        ]);
    }

    private function approval(Student $child): array
    {
        return [
            'student_id' => $child->id,
            'expected_parent_id' => $child->parent_id,
            'verified' => 1,
            'review_note' => 'Relationship verified with centre records.',
        ];
    }

    public function test_registration_creates_a_student_account_owned_by_the_authenticated_parent(): void
    {
        $parent = $this->account();
        $otherParent = $this->account();
        $data = [
            'name' => 'New Child', 'ic' => 'AB-123',
            'date_of_birth' => '2012-01-01', 'form_level' => 2,
            'school_name' => 'Test School', 'email' => 'child@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'parent_id' => $otherParent->id, 'role' => 'admin',
        ];

        $this->actingAs($parent)->post(route('parent.children.store'), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('parent.children.index'));

        $child = Student::sole();
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertSame('AB123', $child->ic);
        $this->assertSame('student', $child->user->role);
        $this->assertTrue(Hash::check('password123', $child->user->password));

        $this->post(route('parent.children.store'), array_merge($data, [
            'ic' => 'ab 123', 'email' => 'duplicate@example.test',
        ]))->assertSessionHasErrors('ic');
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseMissing('users', ['email' => 'duplicate@example.test']);
    }

    public function test_parent_lists_only_their_children_and_requests(): void
    {
        $parent = $this->account();
        $other = $this->account();
        $ownChild = $this->child($parent);
        $otherChild = $this->child($other);
        $ownRequest = $this->linkRequest($parent, $otherChild);
        $this->linkRequest($other, $ownChild);

        $this->actingAs($parent)->get(route('parent.children.index'))
            ->assertOk()
            ->assertViewHas('children', fn ($children) => $children->modelKeys() === [$ownChild->id])
            ->assertViewHas('linkRequests', fn ($requests) => $requests->modelKeys() === [$ownRequest->id]);
    }

    public function test_link_submission_does_not_transfer_ownership_and_rejects_duplicate_pending_requests(): void
    {
        $owner = $this->account();
        $parent = $this->account();
        $child = $this->child($owner);
        $data = [
            'student_name' => $child->name, 'student_ic' => strtolower($child->ic),
            'parent_id' => $owner->id, 'status' => 'approved',
        ];

        $this->actingAs($parent)->post(route('parent.student-link-requests.store'), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('parent.children.index'));
        $this->assertDatabaseHas('student_link_requests', [
            'parent_id' => $parent->id, 'status' => 'pending', 'student_id' => null,
        ]);
        $this->assertEquals($owner->id, $child->fresh()->parent_id);

        $this->post(route('parent.student-link-requests.store'), $data)
            ->assertSessionHasErrors('student_ic');
        $this->assertDatabaseCount('student_link_requests', 1);
    }

    public function test_admin_lists_candidates_and_approves_with_an_audit_record(): void
    {
        $owner = $this->account();
        $parent = $this->account();
        $admin = $this->account('admin');
        $child = $this->child($owner);
        $review = $this->linkRequest($parent, $child);
        $credentials = $child->user->only('email', 'password');

        $this->actingAs($admin)->get(route('admin.student-link-requests.index'))
            ->assertOk()->assertSee($child->name)->assertSee($owner->email);
        $this->patch(route('admin.student-link-requests.approve', $review), $this->approval($child))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.student-link-requests.index'));

        $this->assertEquals($parent->id, $child->fresh()->parent_id);
        $this->assertSame($credentials, $child->fresh()->user->only('email', 'password'));
        $this->assertDatabaseHas('student_link_requests', [
            'id' => $review->id, 'status' => 'approved', 'student_id' => $child->id,
            'previous_parent_id' => $owner->id, 'reviewed_by' => $admin->id,
        ]);
        $this->assertNotNull($review->fresh()->reviewed_at);
        $this->patch(route('admin.student-link-requests.reject', $review), ['review_note' => 'Second review'])
            ->assertSessionHasErrors('request');
        $this->assertSame('approved', $review->fresh()->status);
    }

    public function test_rejection_records_review_without_changing_the_parent(): void
    {
        $owner = $this->account();
        $child = $this->child($owner);
        $review = $this->linkRequest($this->account(), $child);
        $admin = $this->account('admin');

        $this->actingAs($admin)->patch(route('admin.student-link-requests.reject', $review), [
            'review_note' => 'Unable to verify the relationship.',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.student-link-requests.index'));

        $this->assertEquals($owner->id, $child->fresh()->parent_id);
        $this->assertSame('rejected', $review->fresh()->status);
        $this->assertEquals($admin->id, $review->fresh()->reviewed_by);
        $this->assertNotNull($review->fresh()->reviewed_at);
        $this->patch(route('admin.student-link-requests.approve', $review), $this->approval($child))
            ->assertSessionHasErrors('request');
    }

    public function test_approval_rejects_unverified_mismatched_and_stale_requests(): void
    {
        $owner = $this->account();
        $parent = $this->account();
        $child = $this->child($owner);
        $otherChild = $this->child($owner);
        $review = $this->linkRequest($parent, $child);
        $url = route('admin.student-link-requests.approve', $review);
        $this->actingAs($this->account('admin'));

        $this->patch($url, array_merge($this->approval($child), ['verified' => 0]))
            ->assertSessionHasErrors('verified');
        $this->patch($url, $this->approval($otherChild))->assertSessionHasErrors('request');
        $this->patch($url, array_merge($this->approval($child), ['expected_parent_id' => $parent->id]))
            ->assertSessionHasErrors('request');

        $parent->update(['account_status' => 'inactive']);
        $this->patch($url, $this->approval($child))->assertSessionHasErrors('request');
        $this->assertEquals($owner->id, $child->fresh()->parent_id);
        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_routes_require_authentication_and_the_correct_active_role(): void
    {
        $this->get(route('parent.children.index'))->assertRedirect(route('login'));
        $this->post(route('parent.children.store'), [])->assertRedirect(route('login'));
        $this->post(route('parent.student-link-requests.store'), [])->assertRedirect(route('login'));
        $this->get(route('admin.student-link-requests.index'))->assertRedirect(route('login'));

        $parent = $this->account();
        $child = $this->child($this->account());
        $review = $this->linkRequest($parent, $child);
        foreach ([$parent, $this->account('student')] as $user) {
            $this->actingAs($user)->get(route('admin.student-link-requests.index'))->assertForbidden();
            $this->patch(route('admin.student-link-requests.approve', $review), $this->approval($child))
                ->assertForbidden();
            $this->patch(route('admin.student-link-requests.reject', $review), ['review_note' => 'No'])
                ->assertForbidden();
        }

        $this->get(route('parent.children.index'))->assertForbidden();
        $this->post(route('parent.children.store'), [])->assertForbidden();
        $this->post(route('parent.student-link-requests.store'), [])->assertForbidden();
        $parent->update(['account_status' => 'inactive']);
        $this->actingAs($parent)->get(route('parent.children.index'))->assertForbidden();
    }
}
