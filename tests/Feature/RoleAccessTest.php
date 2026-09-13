<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_dietitian_can_access_dashboard(): void
    {
        $dietitian = User::factory()->dietitian()->create();

        $response = $this->actingAs($dietitian)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_dietitian_cannot_access_user_management(): void
    {
        $dietitian = User::factory()->dietitian()->create();

        $response = $this->actingAs($dietitian)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_dietitian_account(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dietitian One',
            'email' => 'dietitian@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'dietitian',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'dietitian@example.com',
            'role' => 'dietitian',
        ]);
    }

    public function test_admin_cannot_create_dietitian_own_account_via_route(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('users.destroy', $admin));

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_first_registration_creates_admin(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'first@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_registration_is_redirected_once_admin_exists(): void
    {
        User::factory()->create();

        $response = $this->get(route('register'));

        $response->assertRedirect(route('home'));
    }
}