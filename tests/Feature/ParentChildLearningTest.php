<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentLinkRequest;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentChildLearningTest extends TestCase 
{
    use RefreshDatabase;

    private User $parent;
    private Student $child;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parent = $this->account();
        $this->child = $this->makeChild($this->parent);

        $this->subject = Subject::create([
            'name' => 'Mathematics',
            'price' => '80.25',
            'is_active' => true,
        ]);
    }

    public function test_subjects_are_approved_unique_and_specific_to_the_child(): void
    {
        $this->enroll($this->child, $this->subject);
        $this->enroll($this->child, $this->subject);

        $sibling = $this->makeChild($this->parent);

        $siblingSubject = Subject::create([
            'name' => 'Sibling Biology',
            'price' => '90.00',
            'is_active' => true,
        ]);

        $this->enroll($sibling, $siblingSubject);

        foreach ([
            'draft',
            'pending_payment',
            'pending_approval',
            'rejected',
            'cancelled',
        ] as $status) {
            $hiddenSubject = Subject::create([
                'name' => 'Hidden '.$status,
                'price' => '50.00',
                'is_active' => true,
            ]);

            $this->enroll($this->child, $hiddenSubject, $status);
        }

        $this->actingAs($this->parent);

        $this->get(route('parent.children.subjects', [
            'child' => $this->child->id,
            'student_id' => $sibling->id,
        ]))
            ->assertOk()
            ->assertViewHas(
                'subjects',
                fn ($subjects) =>
                    $subjects->modelKeys() === [$this->subject->id]
            )
            ->assertSee($this->subject->name)
            ->assertDontSee($siblingSubject->name);

        $this->get(route('parent.children.timetable', [
            'child' => $this->child->id,
        ]))
            ->assertOk()
            ->assertSee('No timetable slots are available for this child.');
    }

    public function test_timetable_matches_subjects_form_and_active_records(): void
    {
        $this->enroll($this->child, $this->subject);

        $tuesday = $this->slot($this->subject, 'Tuesday', '10:00');
        $mondayLate = $this->slot($this->subject, 'Monday', '11:00');
        $mondayEarly = $this->slot($this->subject, 'Monday', '09:00');

        $this->slot($this->subject, 'Wednesday', '09:00', 3);

        $otherSubject = Subject::create([
            'name' => 'Other Child Science',
            'price' => '90.00',
            'is_active' => true,
        ]);

        $this->enroll($this->makeChild($this->parent), $otherSubject);
        $this->slot($otherSubject, 'Thursday', '09:00');

        $inactiveTeacherSlot = $this->slot(
            $this->subject,
            'Saturday',
            '09:00'
        );

        $inactiveTeacherSlot->teacher->update(['is_active' => false]);

        $inactiveSubject = Subject::create([
            'name' => 'Inactive Subject',
            'price' => '60.00',
            'is_active' => false,
        ]);

        $this->enroll($this->child, $inactiveSubject);
        $this->slot($inactiveSubject, 'Sunday', '09:00');

        $this->actingAs($this->parent);

        $this->get(route('parent.children.timetable', [
            'child' => $this->child->id,
        ]))
        ->assertOk()
        ->assertViewHas(
            'slots',
            fn ($slots) => $slots->modelKeys() === [
                $mondayEarly->id,
                $mondayLate->id,
                $tuesday->id,
            ]
        )
        ->assertSee('09:00')
        ->assertSee('10:30')
        ->assertDontSee($otherSubject->name);
    }

    public function test_current_parent_assignment_controls_access(): void
    {
        $this->enroll($this->child, $this->subject);
        $slot = $this->slot($this->subject);

        $newParent = $this->account();
        $unrelatedChild = $this->makeChild($newParent);

        $this->actingAs($this->parent);

        foreach ($this->pageUrls($unrelatedChild) as $url) {
            $this->get($url)->assertNotFound();
        }

        foreach ([
            'parent.children.subjects',
            'parent.children.timetable',
        ] as $routeName) {
            $this->get(route($routeName, ['child' => 999999]))
                ->assertNotFound();
        }

        StudentLinkRequest::create([
            'parent_id' => $newParent->id,
            'student_name' => $this->child->name,
            'student_ic' => str_replace('-', '', $this->child->ic),
            'status' => 'pending',
        ]);

        // A pending link request does not grant access.
        $this->actingAs($newParent);

        foreach ($this->pageUrls($this->child) as $url) {
            $this->get($url)->assertNotFound();
        }

        $this->child->update(['parent_id' => $newParent->id]);

        // The previous parent loses access.
        $this->actingAs($this->parent);

        foreach ($this->pageUrls($this->child) as $url) {
            $this->get($url)->assertNotFound();
        }

        // Approved learning records remain available to the new parent
        $this->actingAs($newParent);

        $this->get(route('parent.children.subjects', [
            'child' => $this->child->id,
        ]))
            ->assertOk()
            ->assertSee($this->subject->name);

        $this->get(route('parent.children.timetable', [
            'child' => $this->child->id,
        ]))
            ->assertOk()
            ->assertViewHas(
                'slots',
                fn ($slots) => $slots->modelKeys() === [$slot->id]
            );
    }

    public function test_payment_without_approval_does_not_unlock_learning(): void 
    {
        $enrollment = $this->enroll(
            $this->child,
            $this->subject,
            'pending_approval'
        );

        $enrollment->payment()->create([
            'payment_method' => 'counter',
            'payment_status' => 'paid',
            'amount' => $this->subject->price,
        ]);

        $this->slot($this->subject);
        $this->actingAs($this->parent);

        $this->get(route('parent.children.subjects', [
            'child' => $this->child->id,
        ]))
            ->assertOk()
            ->assertViewHas('subjects', fn ($subjects) => $subjects->isEmpty())
            ->assertSee('This child has no approved subjects yet.');

        $this->get(route('parent.children.timetable', [
            'child' => $this->child->id,
        ]))
            ->assertOk()
            ->assertViewHas('slots', fn ($slots) => $slots->isEmpty())
            ->assertSee(
                'The timetable becomes available after enrollment approval.'
            );
    }

    public function test_pages_require_an_active_parent_account(): void
    {
        foreach ($this->pageUrls($this->child) as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        foreach (['student', 'admin'] as $role) {
            $this->actingAs($this->account($role));

            foreach ($this->pageUrls($this->child) as $url) {
                $this->get($url)->assertForbidden();
            }
        }

        $this->parent->update(['account_status' => 'inactive']);
        $this->actingAs($this->parent->fresh());

        foreach ($this->pageUrls($this->child) as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function account(string $role = 'parent'): User
    {
        return User::factory()->create([
            'role' => $role,
            'account_status' => 'active',
        ]);
    }

    private function makeChild(User $parent): Student
    {
        $user = $this->account('student');

        return Student::create([
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'name' => 'Child ' .$user->id,
            'ic' => 'CHILD-' .$user->id,
            'date_of_birth' => '2012-01-01',
            'form_level' => 2,
            'school_name' => 'Test School',
        ]);
    }

    private function enroll(
        Student $child,
        Subject $subject,
        string $status = 'approved'
    ): Enrollment {
        $enrollment = Enrollment::create([
            'student_id' => $child->id,
            'parent_id' => $child->parent_id,
            'type' => 'individual',
            'status' => $status,
            'total_amount' => $subject->price,
            'submitted_at' => now(),
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        $enrollment->subjects()->attach($subject->id, [
            'price_at_enrollment' => $subject->price,
        ]);

        return $enrollment;
    }

    private function slot(
        Subject $subject,
        string $day = 'Monday',
        string $startTime = '09:00',
        int $formLevel = 2
    ): Timetable {
        $teacher = Teacher::create([
            'name' => 'Class Teacher',
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ]);

        $teacher->subjects()->attach($subject->id);

        return Timetable::create([
            'day' => $day,
            'start_time' => $startTime,
            'duration_minutes' => 90,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'form_level' => $formLevel,
        ]);
    }

    private function pageUrls(Student $child): array
    {
        return [
            route('parent.children.subjects', ['child' => $child->id]),
            route('parent.children.timetable', ['child' => $child->id]),
        ];
    }
}
