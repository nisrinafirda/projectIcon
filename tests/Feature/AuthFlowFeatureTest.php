<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_does_not_contain_demo_account_shortcuts(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('Klik Cepat Akun Demo');
        $response->assertDontSee('fillAccount');
        $response->assertDontSee('admin@icon.co.id');
        $response->assertDontSee('ahmad@icon.co.id');
    }

    public function test_admin_can_login_with_email_and_password_and_see_admin_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $adminPassword = config('auth.admin_password', 'admin123');

        $response = $this->post(route('login'), [
            'email' => 'admin@gmail.com',
            'password' => $adminPassword,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isAdmin());

        $dashboardResponse = $this->actingAs(auth()->user())->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Dashboard Monitoring Keseluruhan Tugas');
    }

    public function test_regular_user_can_login_with_email_and_password_and_see_user_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $userPassword = config('auth.user_demo_password', 'user123');

        $response = $this->post(route('login'), [
            'email' => 'karyawan@gmail.com',
            'password' => $userPassword,
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isUser());

        $dashboardResponse = $this->actingAs(auth()->user())->get(route('dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Total Tugas Anda');
    }

    public function test_user_registration_validations(): void
    {
        User::factory()->create(['email' => 'existing@gmail.com']);

        // Password kurang dari 8 karakter
        $responseShort = $this->post(route('register'), [
            'name' => 'Budi Baru',
            'email' => 'budi.baru@gmail.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);
        $responseShort->assertSessionHasErrors('password');

        // Password konfirmasi tidak cocok
        $responseMismatch = $this->post(route('register'), [
            'name' => 'Budi Baru',
            'email' => 'budi.baru@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'berbeda123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');

        // Email sudah terdaftar
        $responseDuplicate = $this->post(route('register'), [
            'name' => 'Budi Baru',
            'email' => 'existing@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responseDuplicate->assertSessionHasErrors('email');
    }

    public function test_user_registration_forces_role_user_and_cannot_be_escalated_to_admin(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Karyawan Baru',
            'email' => 'pendaftar@gmail.com',
            'role' => 'admin', // Coba eksploitasi role dari input form
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'pendaftar@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('user', $user->role);
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isAdmin());

        // Middleware role admin menolak akses pendaftar baru
        $adminRouteResponse = $this->actingAs($user)->get(route('admin.verifikasi'));
        $adminRouteResponse->assertStatus(403);
    }

    public function test_database_seeder_cleans_old_dummy_accounts_and_uses_env_password(): void
    {
        // Masukkan dummy lama terlebih dahulu untuk memastikan seeder membersihkannya
        User::create([
            'email' => 'admin@icon.co.id',
            'name' => 'Old Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'email' => 'test@example.com',
            'name' => 'Old Test',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin@icon.co.id']);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'ahmad@icon.co.id']);
        $this->assertDatabaseMissing('users', ['email' => 'siti@icon.co.id']);
        $this->assertDatabaseMissing('users', ['email' => 'budi@icon.co.id']);

        $admin = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);

        $employee = User::where('email', 'karyawan@gmail.com')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('user', $employee->role);

        // Pastikan task terakhir memiliki kategori SO Open
        $lastTask = Task::where('document_number', 'SO-2026-902')->first();
        $this->assertNotNull($lastTask);
        $this->assertEquals(Task::CATEGORY_SO_OPEN, $lastTask->category);
        $this->assertEquals($employee->id, $lastTask->user_id);
    }
}
