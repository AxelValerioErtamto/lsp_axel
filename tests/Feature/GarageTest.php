<?php

namespace Tests\Feature;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GarageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function makeGarage(string $code, GarageType $type): Garage
    {
        return Garage::create(['code' => $code, 'type' => $type, 'daily_price' => 10000]);
    }

    public function test_first_garages_of_a_type_start_at_001(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'bike', 'quantity' => 2, 'daily_price' => 5000])
            ->assertRedirect(route('admin.garages.index'));

        $this->assertSame(['S001', 'S002'], Garage::orderBy('code')->pluck('code')->all());
    }

    public function test_codes_continue_from_the_last_number(): void
    {
        $this->makeGarage('C001', GarageType::Car);
        $this->makeGarage('C002', GarageType::Car);

        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'car', 'quantity' => 3, 'daily_price' => 10000]);

        $this->assertSame(
            ['C001', 'C002', 'C003', 'C004', 'C005'],
            Garage::where('type', GarageType::Car)->orderBy('code')->pluck('code')->all(),
        );
        $this->assertSame(GarageStatus::Available, Garage::where('code', 'C005')->first()->status);
    }

    public function test_creating_up_to_code_999_is_accepted(): void
    {
        $this->makeGarage('S997', GarageType::Bike);

        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'bike', 'quantity' => 2, 'daily_price' => 5000])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('garages', ['code' => 'S999']);
    }

    public function test_creating_past_code_999_is_rejected(): void
    {
        $this->makeGarage('S998', GarageType::Bike);

        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'bike', 'quantity' => 2, 'daily_price' => 5000])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('garages', 1);
    }

    public static function invalidQuantityCases(): array
    {
        return [
            'zero' => [0],
            'more than 999 at once' => [1000],
        ];
    }

    #[DataProvider('invalidQuantityCases')]
    public function test_invalid_quantity_is_rejected(int $quantity): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'car', 'quantity' => $quantity, 'daily_price' => 10000])
            ->assertSessionHasErrors('quantity');
    }

    public function test_electric_garage_requires_electricity_price(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'electric', 'quantity' => 1, 'daily_price' => 15000])
            ->assertSessionHasErrors('electricity_price');
    }

    public function test_electricity_price_is_ignored_for_other_garage_types(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/garages', ['type' => 'car', 'quantity' => 1, 'daily_price' => 10000, 'electricity_price' => 2500]);

        $this->assertNull(Garage::first()->electricity_price);
    }

    public function test_admin_sees_the_garage_list(): void
    {
        $this->makeGarage('C001', GarageType::Car);

        $this->actingAs($this->admin())
            ->get('/admin/garages')
            ->assertOk()
            ->assertSee('C001');
    }

    public function test_tenant_cannot_open_admin_pages(): void
    {
        $tenant = User::factory()->create();

        $this->actingAs($tenant)->get('/admin/garages')->assertForbidden();
    }
}
