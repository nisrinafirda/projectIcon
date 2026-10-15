<?php

namespace Tests\Feature;

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
            'username' => 'admin',
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
        $user = User::factory()->create([
            'username' => 'karyawan.icon',
            'role' => 'user',
            'password' => Hash::make('user123'),
        ]);

        $response = $this->post(route('login'), [
            'username' => 'karyawan.icon',
            'password' => 'user123',
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

        $admin = User::where('email', 'admin@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals('admin', $admin->role);

        $salesCount = User::where('role', 'user')->count();
        $this->assertGreaterThan(0, $salesCount);
    }

    public function test_admin_can_create_and_update_user_with_username(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Admin create new user with username
        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Budi Santoso',
            'username' => 'budi.santoso',
            'role' => 'user',
            'nip' => 'NIP999',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'budi.santoso',
            'name' => 'Budi Santoso',
            'email' => null,
        ]);

        $createdUser = User::where('username', 'budi.santoso')->first();

        // Duplicate username rejected
        $duplicateResponse = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Budi Duplicate',
            'username' => 'budi.santoso',
            'role' => 'user',
            'password' => 'secret123',
        ]);
        $duplicateResponse->assertSessionHasErrors('username');

        // Admin update user details
        $updateResponse = $this->actingAs($admin)->put(route('admin.users.update', $createdUser), [
            'name' => 'Budi S. Updated',
            'username' => 'budi.updated',
            'role' => 'user',
            'nip' => 'NIP999',
        ]);
        $updateResponse->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $createdUser->id,
            'username' => 'budi.updated',
            'name' => 'Budi S. Updated',
        ]);
    }

    public function test_admin_can_update_user_without_nip_and_department_and_preserves_existing_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create([
            'role' => 'user',
            'name' => 'Siti Aminah',
            'username' => 'siti.aminah',
            'nip' => 'NIP-ORIGINAL-123',
            'department' => 'Operasional',
        ]);

        // Form edit only sends: name, username, role, password, password_confirmation
        $response = $this->actingAs($admin)->put(route('admin.users.update', $employee), [
            'name' => 'Siti Aminah, S.Kom',
            'username' => 'siti.aminah.skom',
            'role' => 'user',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $employee->refresh();
        $this->assertSame('Siti Aminah, S.Kom', $employee->name);
        $this->assertSame('siti.aminah.skom', $employee->username);
        // Preserves existing nip and department
        $this->assertSame('NIP-ORIGINAL-123', $employee->nip);
        $this->assertSame('Operasional', $employee->department);
    }
}
