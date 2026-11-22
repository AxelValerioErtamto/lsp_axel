<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TenantVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_only_pending_tenants(): void
    {
        $pending = User::factory()->pending()->create(['name' => 'Penyewa Baru']);
        $approved = User::factory()->create(['name' => 'Penyewa Lama']);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/tenants')
            ->assertOk()
            ->assertSee($pending->name)
            ->assertDontSee($approved->name);
    }

    public function test_admin_approves_tenant_who_can_then_log_in(): void
    {
        $tenant = User::factory()->pending()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/admin/tenants/{$tenant->id}/approve")
            ->assertRedirect(route('admin.tenants.index'));

        $this->assertSame(UserStatus::Approved, $tenant->fresh()->status);

        Auth::logout();
        $this->post('/login', ['email' => $tenant->email, 'password' => 'password']);
        $this->assertAuthenticatedAs($tenant);
    }

    public function test_admin_rejects_tenant_who_then_cannot_log_in(): void
    {
        $tenant = User::factory()->pending()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/admin/tenants/{$tenant->id}/reject");

        $this->assertSame(UserStatus::Rejected, $tenant->fresh()->status);

        Auth::logout();
        $this->post('/login', ['email' => $tenant->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_only_pending_tenants_can_be_verified(): void
    {
        $approved = User::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch("/admin/tenants/{$approved->id}/reject")
            ->assertNotFound();

        $this->assertSame(UserStatus::Approved, $approved->fresh()->status);
    }
}