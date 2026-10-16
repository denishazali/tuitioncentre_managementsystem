<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Package;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPackageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Subject $math;
    private Subject $science;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->math = Subject::create([
            'name' => 'Mathematics',
            'price' => '80.25',
            'is_active' => true,
        ]);

        $this->science = Subject::create([
            'name' => 'Science',
            'price' => '90.50',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_list_edit_and_update_packages(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.packages.create'))
            ->assertOk()
            ->assertSee('Compulsory subjects')
            ->assertSee('Elective subjects');

        $this->post(route('admin.packages.store'), $this->payload())
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHasNoErrors();

        $package = Package::sole();

        $this->assertSame('150.00', $package->price);

        $this->assertDatabaseHas('package_subject', [
            'package_id' => $package->id,
            'subject_id' => $this->math->id,
            'is_compulsory' => true,
        ]);

        $this->assertDatabaseHas('package_subject', [
            'package_id' => $package->id,
            'subject_id' => $this->science->id,
            'is_compulsory' => false,
        ]);

        $this->get(route('admin.packages.index'))
            ->assertOk()
            ->assertSee('Core Bundle');

        $this->get(route('admin.packages.edit', $package))
            ->assertOk()
            ->assertViewHas('compulsoryIds', [$this->math->id])
            ->assertViewHas('electiveIds', [$this->science->id]);

        $this->put(
            route('admin.packages.update', $package),
            $this->payload([
                'name' => 'Revised Bundle',
                'price' => '160.00',
                'compulsory_ids' => [$this->science->id],
                'elective_ids' => [],
                'elective_subject_limit' => 0,
            ])
        )
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Revised Bundle', $package->fresh()->name);
        $this->assertSame('160.00', $package->fresh()->price);

        $this->assertDatabaseMissing('package_subject', [
            'package_id' => $package->id,
            'subject_id' => $this->math->id,
        ]);

        $this->assertDatabaseHas('package_subject', [
            'package_id' => $package->id,
            'subject_id' => $this->science->id,
            'is_compulsory' => true,
        ]);
    }

    public function test_invalid_package_selections_are_rejected(): void
    {
        $this->actingAs($this->admin);

        $cases = [
            [
                [
                    'compulsory_ids' => [],
                    'elective_ids' => [],
                    'elective_subject_limit' => 0,
                ],
                'compulsory_ids',
            ],
            [
                ['elective_ids' => [$this->math->id]],
                'elective_ids',
            ],
            [
                ['elective_subject_limit' => 2],
                'elective_subject_limit',
            ],
            [
                [
                    'compulsory_ids' => [],
                    'elective_subject_limit' => 0,
                ],
                'elective_subject_limit',
            ],
            [
                ['compulsory_ids' => [999999]],
                'compulsory_ids.0',
            ],
            [
                ['price' => '-1.00'],
                'price',
            ],
            [
                ['price' => '1.001'],
                'price',
            ],
        ];

        foreach ($cases as [$overrides, $field]) {
            $this->post(
                route('admin.packages.store'),
                $this->payload($overrides)
            )->assertSessionHasErrors($field);

            $this->assertDatabaseCount('packages', 0);
            $this->assertDatabaseCount('package_subject', 0);
        }
    }

    public function test_inactive_subjects_block_activation_but_allow_deactivation(): void
    {
        $package = $this->makePackage();
        $this->math->update(['is_active' => false]);

        $this->actingAs($this->admin);

        $this->put(
            route('admin.packages.update', $package),
            $this->payload()
        )->assertSessionHasErrors('is_active');

        $this->put(
            route('admin.packages.update', $package),
            $this->payload(['is_active' => 0])
        )
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse($package->fresh()->is_active);

        $this->get(route('admin.packages.edit', $package))
            ->assertOk()
            ->assertSee('Mathematics (inactive)');

        $parent = User::factory()->create([
            'role' => 'parent',
            'account_status' => 'active',
        ]);

        $this->actingAs($parent)
            ->get(route('parent.enrollments.index'))
            ->assertOk()
            ->assertDontSee('Core Bundle');
    }

    public function test_updates_preserve_enrollment_snapshots_and_block_deletion(): void
    {
        $package = $this->makePackage();

        $parent = User::factory()->create([
            'role' => 'parent',
            'account_status' => 'active',
        ]);

        $studentUser = User::factory()->create([
            'role' => 'student',
            'account_status' => 'active',
        ]);

        $student = Student::create([
            'user_id' => $studentUser->id,
            'parent_id' => $parent->id,
            'name' => 'Test Child',
            'ic' => 'PACKAGE-'.$studentUser->id,
            'date_of_birth' => '2012-01-01',
            'form_level' => 2,
            'school_name' => 'Test School',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'parent_id' => $parent->id,
            'package_id' => $package->id,
            'type' => 'bundle',
            'status' => 'approved',
            'total_amount' => '150.00',
            'submitted_at' => now(),
            'approved_at' => now(),
        ]);

        $enrollment->subjects()->attach($this->math->id, [
            'price_at_enrollment' => $this->math->price,
        ]);

        $this->actingAs($this->admin);

        $this->put(
            route('admin.packages.update', $package),
            $this->payload([
                'price' => '180.00',
                'compulsory_ids' => [$this->science->id],
                'elective_ids' => [],
                'elective_subject_limit' => 0,
            ])
        )
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('180.00', $package->fresh()->price);
        $this->assertSame('150.00', $enrollment->fresh()->total_amount);

        $this->assertSame(
            [$this->math->id],
            $enrollment->subjects()->get()->modelKeys()
        );

        $this->assertDatabaseHas('enrollment_subject', [
            'enrollment_id' => $enrollment->id,
            'subject_id' => $this->math->id,
            'price_at_enrollment' => '80.25',
        ]);

        $this->delete(route('admin.packages.destroy', $package))
            ->assertSessionHasErrors('record');

        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_unused_packages_can_be_deleted(): void
    {
        $package = $this->makePackage();

        $this->actingAs($this->admin)
            ->delete(route('admin.packages.destroy', $package))
            ->assertRedirect(route('admin.packages.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('packages', ['id' => $package->id]);

        $this->assertDatabaseMissing('package_subject', [
            'package_id' => $package->id,
        ]);
    }

    public function test_routes_require_an_active_admin(): void
    {
        $package = $this->makePackage();

        $requests = [
            ['GET', route('admin.packages.index')],
            ['GET', route('admin.packages.create')],
            ['POST', route('admin.packages.store')],
            ['GET', route('admin.packages.edit', $package)],
            ['PUT', route('admin.packages.update', $package)],
            ['DELETE', route('admin.packages.destroy', $package)],
        ];

        foreach ($requests as [$method, $url]) {
            $this->call($method, $url)
                ->assertRedirect(route('login'));
        }

        foreach (['parent', 'student'] as $role) {
            $this->actingAs(User::factory()->create([
                'role' => $role,
                'account_status' => 'active',
            ]));

            foreach ($requests as [$method, $url]) {
                $this->call($method, $url)->assertForbidden();
            }
        }

        $this->admin->update(['account_status' => 'inactive']);
        $this->actingAs($this->admin->fresh());

        foreach ($requests as [$method, $url]) {
            $this->call($method, $url)->assertForbidden();
        }
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Core Bundle',
            'price' => '150.00',
            'elective_subject_limit' => 1,
            'is_active' => 1,
            'compulsory_ids' => [$this->math->id],
            'elective_ids' => [$this->science->id],
        ], $overrides);
    }

    private function makePackage(): Package
    {
        $package = Package::create([
            'name' => 'Core Bundle',
            'price' => '150.00',
            'elective_subject_limit' => 1,
            'is_active' => true,
        ]);

        $package->subjects()->attach([
            $this->math->id => ['is_compulsory' => true],
            $this->science->id => ['is_compulsory' => false],
        ]);

        return $package;
    }
}