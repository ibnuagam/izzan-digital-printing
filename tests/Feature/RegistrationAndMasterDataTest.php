<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationAndMasterDataTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $extra = []): array
    {
        return array_replace(['name' => 'Pelanggan Uji', 'email' => 'uji@example.test', 'phone' => '081234567890', 'password' => 'Password2026!', 'password_confirmation' => 'Password2026!'], $extra);
    }

    public function test_registration_ignores_injected_role_and_hashes_password(): void
    {
        $this->get('/register')->assertOk()->assertSee('Buat akun pelanggan');
        $this->post('/register', $this->registration(['role' => 'admin']))->assertRedirect('/pelanggan/dashboard');
        $user = User::where('email', 'uji@example.test')->firstOrFail();
        $this->assertSame('pelanggan', $user->role);
        $this->assertSame('081234567890', $user->phone);
        $this->assertTrue(Hash::check('Password2026!', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->get('/admin/services')->assertForbidden();
        $this->post('/logout');
        $this->post('/login', ['email' => ' UJI@EXAMPLE.TEST ', 'password' => 'Password2026!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_email_password_confirmation_and_phone_are_validated(): void
    {
        User::factory()->create(['email' => 'uji@example.test']);
        $this->post('/register', $this->registration(['email' => ' UJI@example.test ', 'password_confirmation' => 'wrong', 'phone' => 'invalid']))->assertSessionHasErrors(['email', 'password', 'phone']);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_registration_rejects_weak_password_and_is_rate_limited(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.40']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', $this->registration(['password' => 'abcdefgh', 'password_confirmation' => 'abcdefgh']))->assertSessionHasErrors('password');
        }
        $this->post('/register', $this->registration())->assertStatus(429);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_can_create_edit_search_and_deactivate_master_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (['services', 'materials'] as $type) {
            $row = ['code' => 'TEST-01', 'name' => 'Nama uji', 'description' => 'Keterangan uji', 'unit' => 'm2', 'is_active' => 1];
            if ($type === 'services') {
                $row['base_price'] = '25000.50';
            }
            $this->get('/admin/'.$type.'/create')->assertOk();
            $this->post('/admin/'.$type, $row)->assertRedirect('/admin/'.$type);
            $model = $type === 'services' ? Service::class : Material::class;
            $record = $model::firstOrFail();
            $this->get('/admin/'.$type.'/'.$record->id.'/edit')->assertOk()->assertSee('Nama uji');
            $this->put('/admin/'.$type.'/'.$record->id, array_replace($row, ['name' => 'Diperbarui', 'is_active' => 0]))->assertRedirect('/admin/'.$type);
            $this->assertDatabaseHas($type, ['id' => $record->id, 'name' => 'Diperbarui', 'is_active' => 0]);
            $this->get('/admin/'.$type.'?q=Diperbarui&status=inactive')->assertOk()->assertSee('Diperbarui');
            $this->get('/admin/'.$type.'?status=active')->assertOk()->assertDontSee('Diperbarui');
        }
    }

    public function test_non_admin_cannot_read_or_write_master_data(): void
    {
        foreach (['pelanggan', 'manajer'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach (['services', 'materials'] as $type) {
                $this->get('/admin/'.$type)->assertForbidden();
                $this->post('/admin/'.$type, [])->assertForbidden();
                $this->put('/admin/'.$type.'/1', [])->assertForbidden();
            }
        }
    }

    public function test_master_validation_rejects_duplicate_code_invalid_unit_and_negative_price(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Service::create(['code' => 'TEST-01', 'name' => 'Uji', 'unit' => 'pcs', 'is_active' => true]);
        $this->post('/admin/services', ['code' => 'TEST-01', 'name' => 'Uji', 'unit' => 'invalid', 'base_price' => -1, 'is_active' => 1])->assertSessionHasErrors(['code', 'unit', 'base_price']);
        $this->assertDatabaseCount('services', 1);
    }

    public function test_catalog_only_displays_active_services_and_escapes_description(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'pelanggan']));
        Service::create(['code' => 'ACT', 'name' => 'Layanan aktif', 'unit' => 'pcs', 'description' => '<script>alert(1)</script>', 'base_price' => 1000, 'is_active' => true]);
        Service::create(['code' => 'OFF', 'name' => 'Layanan nonaktif', 'unit' => 'pcs', 'is_active' => false]);
        $this->get('/pelanggan/catalog')->assertOk()->assertSee('Layanan aktif')->assertDontSee('Layanan nonaktif')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/pelanggan/catalog?q=TidakAda')->assertOk()->assertDontSee('Layanan aktif');
    }
}
