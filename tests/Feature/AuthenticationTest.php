<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_forms_render_and_registration_creates_a_parent(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="password"', false);
        $this->get('/register')->assertOk()->assertSee('name="password_confirmation"', false);
        $this->post('/register', [
            'name' => 'New Parent', 'email' => 'parent@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'parent@example.test', 'role' => 'parent']);
        $this->get('/parent/dashboard')->assertOk()->assertSee('New Parent');
        $this->get('/admin/dashboard')->assertForbidden();
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_users_log_in_to_their_role_dashboards(): void
    {
        foreach (['admin', 'parent', 'student'] as $role) {
            $user = User::factory()->create(['role' => $role, 'account_status' => 'active']);
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route($role.'.dashboard'));
            $this->get('/'.$role.'/dashboard')->assertOk()->assertSee('dashboard');
            $this->post('/logout');
        }
    }

    public function test_pending_inactive_and_invalid_logins_are_rejected(): void
    {
        foreach (['pending', 'inactive'] as $status) {
            $user = User::factory()->create(['role' => 'admin', 'account_status' => $status]);
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
            $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
            $this->post('/logout');
        }
        $this->post('/login', ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
