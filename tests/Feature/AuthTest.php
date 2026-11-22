<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'nik' => '3578012345678901',
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    public function test_tenant_registers_with_pending_status(): void
    {
        $response = $this->post('/register', $this->validRegistration());

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertSame(Role::Tenant, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
    }

    public static function invalidNikCases(): array
    {
        return [
            '15 digits' => ['357801234567890'],
            '17 digits' => ['35780123456789012'],
            'contains letters' => ['35780123456789AB'],
        ];
    }

    #[DataProvider('invalidNikCases')]
    public function test_registration_rejects_invalid_nik(string $nik): void
    {
        $response = $this->post('/register', $this->validRegistration(['nik' => $nik]));

        $response->assertSessionHasErrors('nik');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_nik(): void
    {
        User::factory()->create(['nik' => '3578012345678901']);

        $response = $this->post('/register', $this->validRegistration());

        $response->assertSessionHasErrors('nik');
    }

    public function test_pending_tenant_cannot_log_in(): void
    {
        $user = User::factory()->pending()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rejected_tenant_cannot_log_in(): void
    {
        $user = User::factory()->rejected()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_approved_tenant_can_log_in(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_can_log_in(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'password']);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'salah']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_sent_to_login_page(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
