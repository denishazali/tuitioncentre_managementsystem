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
        parent::setup();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'active',
        ]);

        $this->math = Subject::create([
            'name' => 'Science',
            'price' => '90.50',
            'is_active'=> true,
        ]);
    }

    public function test_admin_can_create_list_edit_and_update_packages(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('admin.package.create'))
            ->assertOk()
            ->assertSee('Compulsorysubjects')
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

        $this->get(route('admin.package.edit', $package))
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
                    
                ]
            ]
        ]
    }
}
