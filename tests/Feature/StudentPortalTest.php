<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
Use App\Models\User;
Use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = $this->makeStudent();

        $this->subject = Subject::create([
            'name' => 'Mathematics',
            'price' => '80.25',
            'is_active'=> true,
        ]);
    }

    public function test_subjects_are_approved_unique_and_owned_by_the_student(): void
    {
        $this->enroll($this->student, $this->subject);
        $this->enroll($this->student, $this->subject);

        $otherStudent = $this->makeStudent();

        $otherSubject = Subject::create([
            'name' => 'Science',
            'price' => '90.00',
            'is_active' => true,
        ]);

        $this->enroll($otherStudent, $otherSubject);

        foreach ([
            'draft',
            'pending_payment',
            'pending_approval',
            'rejected',
            'cancelled',
        ] as $status) {
            $hiddenSubject = Subject::create([
                'name' => 'Hidden ' .$status,
                'price' => '50.00',
                'is_active' => true,
            ]);

            $enrollment = $this->enroll(
                $this->student,
                $hiddenSubject,
                $status
            );

            if ($status === 'pending_approval') {
                $enrollment->payment()->create([
                    'payment_method' => 'counter',
                    'payment_status' => 'paid',
                    'amount' => $hiddenSubject->price,
                ]);
            }
        }

        $this->actingAs($this->student->user);

        $this->get(route('student.subjects.index', [
            'student_id' => $otherStudent->id,
        ]))
            ->assertOk()
            ->assertViewHas(
                'subjects',
                fn ($subjects) =>
                    $subjects->modelKeys() === [$this->subject->id]
            );

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('subjectCount', 1);
    }

    public function test_timetale_filters_subjects_form_and_active_records(): void
    {
        $this->enroll($this->student, $this->subject);

        $tuesday = $this->slot($this->subject, 'Tuesday', '10:00');
        $mondayLate = $this->slot($this->subject, 'Monday', '11:00');
        $mondayEarly = $this->slot($this->subject, 'Monday', '09:00');

        // Another form level must not appear.
        $this->slot($this->subject, 'Wednesday', '09:00', 3);

        // A subject belonging to another student must not appear.
        $otherSubject = Subject::create([
            'name' => 'History',
            'price' => '70.00',
            'is_active' => true,
        ]);

        $this->enroll($this->makeStudent(), $otherSubject);
        $this->slot($otherSubject, 'Thursday', '09:00');

        // Payment without approval must not unlock a class.
        $pendingSubject = Subject::create([
            'name' => 'Pending Science',
            'price' => '90.00',
            'is_active' => true,
        ]);

        $pending = $this->enroll(
            $this->student,
            $pendingSubject,
            'pending_approval'
        );

        $pending->payment()->create([
            'payment_method' => 'counter',
            'payment_status' => 'paid',
            'amount' => $pendingSubject->price,
        ]);

        $this->slot($pendingSubject, 'Friday', '09:00');

        $inactiveTeacherSlot = $this->slot(
            $this->subject,
            'Saturday',
            '09:00'
        );

        $inactiveTeacherSlot->teacher->update(['is_active' => false]);

        $inactiveSubject = Subject::create([
            'name' => 'Inactive Subject',
            'price' => '60.00',
            'is_active'=> false,
        ]);

        $this->enroll($this->student, $inactiveSubject);
        $this->slot($inactiveSubject, 'Sunday', '09:00');

        $this->actingAs($this->student->user);

        $this->get(route('student.timetable.index'))
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
        ->assertSee('10:30');

    $this->get(route('student.dashboard'))
        ->assertOk()
        ->assertViewHas('classCount', 3);
    }

    public function test_paid_enrollment_still_requires_approval(): void
    {
        $enrollment = $this->enroll(
            $this->student,
            $this->subject,
            'pending_approval'
        );

        $enrollment->payment()->create([
            'payment_method' => 'counter',
            'payment_status' => 'paid',
            'amount' => $this->subject->price,
        ]);

        $this->slot($this->subject);
        $this->actingAs($this->student->user);

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('subjectCount', 0)
            ->assertViewHas('classCount', 0);

        $this->get(route('student.subjects.index'))
            ->assertOk()
            ->assertViewHas('subjects', fn ($subjects) => $subjects->isEmpty());

        $this->get(route('student.timetable.index'))
            ->assertOk()
            ->assertViewHas('slots', fn ($slots) => $slots->isEmpty());
    }

    public function test_pages_handle_empty_enrollments_and_missing_profiles(): void
    {
        $this->actingAs($this->student->user);

        $this->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('subjectCount', 0)
            ->assertViewHas('classCount', 0);

        $this->get(route('student.subjects.index'))
            ->assertOk()
            ->assertSee('You have no approved subjects yet.');

        $this->get(route('student.timetable.index'))
            ->assertOk()
            ->assertSee(
                'Your timetable becomes available after enrollment approval.'
            );
        
        $withoutProfile = User::factory()->create([
            'role' => 'student',
            'account_status' => 'active',
        ]);

        $this->actingAs($withoutProfile);

        foreach ($this->portalRoutes() as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee(
                    'Your student profile has not been set up. Please contact the centre.'
                );
        }
    }

    public function test_portal_requires_an_active_student_account(): void
    {
        foreach ($this->portalRoutes() as $routeName) {
            $this->get(route($routeName))
                ->assertRedirect(route('login'));
        }

        foreach(['parent', 'admin'] as $role) {
            $this->actingAs(User::factory()->create([
                'role' => $role,
                'account_status' => 'active',
            ]));

            foreach ($this->portalRoutes() as $routeName) {
                $this->get(route($routeName))->assertForbidden();
            }
        }

        $this->student->user->update(['account_status' => 'inactive']);
        $this->actingAs($this->student->user->fresh());

        foreach ($this->portalRoutes() as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    }

    private function makeStudent(): Student
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'account_status' => 'active',
        ]);

        $user = User::factory()->create([
            'role' => 'student',
            'account_status' => 'active',
        ]);

        return Student::create([
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'name' => 'Test Student',
            'ic' => 'TEST-'.$user->id,
            'date_of_birth' => '2012-01-01',
            'form_level' => 2,
            'school_name' => 'Test School',
        ]);
    }

    private function enroll(
        Student $student,
        Subject $subject,
        string $status = 'approved'
    ): Enrollment {
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'parent_id' => $student->parent_id,
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

    private function portalRoutes(): array
    {
        return [
            'student.dashboard',
            'student.subjects.index',
            'student.timetable.index',
        ];
    }
}