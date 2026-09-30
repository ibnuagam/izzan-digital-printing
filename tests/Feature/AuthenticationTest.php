<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_each_role_can_login_and_only_access_its_dashboard(): void
    {
        foreach (['admin', 'manajer', 'pelanggan'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);
            $this->get('/dashboard')->assertRedirect('/'.$role.'/dashboard');
            foreach (['admin', 'manajer', 'pelanggan'] as $target) {
                $response = $this->get('/'.$target.'/dashboard');
                $role === $target ? $response->assertOk() : $response->assertForbidden();
            }
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_wrong_password_is_rejected_and_attempts_are_limited(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
