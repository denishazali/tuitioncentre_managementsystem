<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
Use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private Student $child;
    private Subject $compulsory;
    private Subject $elective;
    private Package $package;

    protected function setUp(): void
    {
        parent::setUp();

        $parent = User::factory()->create(['role' => 'parent', 'account_status' => 'active']);
        $student = User::factory()->create(['role' => 'student', 'account_status' => 'active']);
        $this->child = Student::create([
            'user_id' => $student->id, 'parent_id' => $parent->id,
            'name' => 'Test Child', 'ic' => 'TEST123', 'date_of_birth' => '2012-01-01',
            'form_level' => 2, 'school_name' => 'Test School',
        ]);
        $this->compulsory = Subject::create(['name' => 'Mathematics', 'price' => '80.25', 'is_active' => true]);
        $this->elective = Subject::create(['name' => 'Science', 'price' => '90.50', 'is_active' => true]);
        $this->package = Package::create([
            'name' => 'Test Package', 'price' => '150.00',
            'elective_subject_limit' => 1, 'is_active' => true,
        ]);
        $this->package->subjects()->attach([
            $this->compulsory->id => ['is_compulsory' => true],
            $this->elective->id => ['is_compulsory' => false],
        ]);
        $this->actingAs($parent);
    }

    public function test_catalogue_and_both_selection_forms_render(): void
    {
        $this->get(route('parent.enrollments.index'))->assertOk()
            ->assertSee('Test Package')->assertSee('Mathematics');
        $this->get(route('parent.enrollments.create', ['type' => 'individual']))
            ->assertOk()->assertSee('Test Child')->assertSee('Science');
        $this->get(route('parent.enrollments.create', ['type' => 'bundle', 'package_id' => $this->package->id]))
            ->assertOk()->assertSee('Test Package')->assertSee('Mathematics')->assertSee('Science');
    }

    public function test_individual_preview_uses_database_prices_without_saving_enrollment(): void
    {
        $this->post(route('parent.enrollments.preview'), [
            'student_id' => $this->child->id, 'type' => 'individual',
            'subject_ids' => [$this->compulsory->id, $this->elective->id],
            'total_amount' => '0.01',
        ])->assertOk()->assertViewHas('total', '170.75');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_package_preview_includes_compulsory_subjects_and_uses_fixed_price(): void
    {
        $this->post(route('parent.enrollments.preview'), [
            'student_id' => $this->child->id, 'type' => 'bundle',
            'package_id' => $this->package->id, 'subject_ids' => [$this->elective->id],
        ])->assertOk()->assertViewHas('total', '150.00')
            ->assertSee('Mathematics')->assertSee('Science');

        $this->post(route('parent.enrollments.preview'), [
            'student_id' => $this->child->id, 'type' => 'bundle',
            'package_id' => $this->package->id,
        ])->assertOk()->assertViewHas('subjects', fn ($subjects) => $subjects->modelKeys() === [$this->compulsory->id]);
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_invalid_electives_and_excess_selections_are_rejected(): void
    {
        $other = Subject::create(['name' => 'History', 'price' => 70, 'is_active' => true]);
        $data = ['student_id' => $this->child->id, 'type' => 'bundle', 'package_id' => $this->package->id];

        $this->post(route('parent.enrollments.preview'), $data + ['subject_ids' => [$other->id]])
            ->assertSessionHasErrors('subject_ids');
        $this->package->subjects()->attach($other->id, ['is_compulsory' => false]);
        $this->post(route('parent.enrollments.preview'), $data + ['subject_ids' => [$other->id, $this->elective->id]])
            ->assertSessionHasErrors('subject_ids');
    }

    public function test_inactive_subjects_and_incomplete_packages_are_unavailable(): void
    {
        $this->compulsory->update(['is_active' => false]);
        $this->get(route('parent.enrollments.index'))->assertOk()->assertDontSee('Test Package');
        $this->get(route('parent.enrollments.create', ['type' => 'bundle', 'package_id' => $this->package->id]))
            ->assertNotFound();
        $this->post(route('parent.enrollments.preview'), [
            'student_id' => $this->child->id, 'type' => 'individual', 'subject_ids' => [$this->compulsory->id],
        ])->assertSessionHasErrors('subject_ids');
    }

    public function test_parent_cannot_preview_another_parents_child(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'parent', 'account_status' => 'active']));
        $this->get(route('parent.enrollments.create', ['type' => 'individual']))
            ->assertRedirect(route('parent.children.index'));
        $this->post(route('parent.enrollments.preview'), [
            'student_id' => $this->child->id, 'type' => 'individual', 'subject_ids' => [$this->elective->id],
        ])->assertSessionHasErrors('student_id');
    }

    public function test_student_cannot_access_parent_enrollment_routes(): void
    {
        $this->actingAs($this->child->user);
        $this->get(route('parent.enrollments.index'))->assertForbidden();
        $this->get(route('parent.enrollments.create', ['type' => 'individual']))->assertForbidden();
        $this->post(route('parent.enrollments.preview'), [])->assertForbidden();
    }

    public function test_counter_payment_and_approval_flow(): void
    {
        $parent = $this->child->parent;
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $preview = $this->post(route('parent.enrollments.preview'), [
            'student_id' => $this->child->id,
            'type' => 'individual',
            'subject_ids' => [$this->compulsory->id], 
        ])->assertOk();

        $this->post(route('parent.enrollments.store'), [
            'quote_token' => $preview->viewData('quoteToken'),
            'total_amount' => '0.01',
            'status' => 'approved',
        ])->assertRedirect(route('parent.enrollments.history'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseCount('payments', 1);

        $enrollment = Enrollment::sole();
        $payment = Payment::sole();

        $this->assertSame('pending_payment', $enrollment->status);
        $this->assertSame('80.25', $enrollment->total_amount);
        $this->assertSame($parent->id, $enrollment->parent_id);
        $this->assertSame('counter', $payment->payment_method);
        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame('80.25', $payment->amount);

        $this->get(route('parent.enrollments.history'))
            ->assertOk()
            ->assertSee('Pending Payment');

        $this->actingAs($admin);

        // Approval cannot skip payment confirmation.
        $this->patch(route('admin.enrollments.update', $enrollment), [
            'action' => 'approve',
        ])->assertSessionHasErrors('enrollment');

        $this->assertSame('pending_payment', $enrollment->fresh()->status);

        // Payment confirmation does not require a rejection reason.
        $this->patch(route('admin.enrollments.update', $enrollment), [
            'action' => 'paid',
            'received_amount' => '80.25',
            'payment_received' => '1',
        ])->assertRedirect(route('admin.enrollments.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('pending_approval', $enrollment->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame($admin->id, $payment->fresh()->approved_by);
        $this->assertNotNull($payment->fresh()->paid_at);

        $this->get(route('admin.enrollments.index'))
        ->assertOk()
            ->assertSee('Pending Approval');

        $this->patch(route('admin.enrollments.update', $enrollment), [
            'action' => 'approve',
        ])->assertRedirect(route('admin.enrollments.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $enrollment->fresh()->status);
        $this->assertNotNull($enrollment->fresh()->approved_at);

        $this->actingAs($parent);

        $this->get(route('parent.enrollments.history'))
            ->assertOk()
            ->assertSee('Your enrollment has been approved.');
    }
}
