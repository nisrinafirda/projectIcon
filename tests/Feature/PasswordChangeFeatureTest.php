<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_password_edit_page(): void
    {
        $response = $this->get(route('password.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_password_change_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.edit'));
        $response->assertStatus(200);
        $response->assertSee('Ganti Kata Sandi');
        $response->assertSee('Kata Sandi Saat Ini');
        $response->assertSee('Kata Sandi Baru');
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password-123',
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_user_can_successfully_change_password(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'old-password-123',
            'password' => 'brand-new-secret-123',
            'password_confirmation' => 'brand-new-secret-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('brand-new-secret-123', $user->fresh()->password));
    }
}
