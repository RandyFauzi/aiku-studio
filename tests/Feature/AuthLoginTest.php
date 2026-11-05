<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_with_illustration_and_without_google_or_register(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('assets/object/Login page.gif');
        $response->assertSee('Login');
        $response->assertSee('Masuk ke akunmu');
        $response->assertDontSee('Masuk dengan Google');
        $response->assertDontSee('Daftar sekarang');
    }

    public function test_user_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'contributor@aiku.test',
            'password' => Hash::make('secret123'),
            'role' => 'contributor',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'contributor@aiku.test',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/me');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_name_or_username(): void
    {
        $user = User::factory()->create([
            'name' => 'Aufa Anugraha',
            'email' => 'aufa@aiku.test',
            'password' => Hash::make('secret123'),
            'role' => 'contributor',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'Aufa Anugraha',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/me');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@aiku.test',
            'password' => Hash::make('secret123'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'nonaktif@aiku.test',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_invalid_credentials_rejected(): void
    {
        User::factory()->create([
            'email' => 'valid@aiku.test',
            'password' => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'valid@aiku.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
