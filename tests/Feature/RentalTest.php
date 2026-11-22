<?php

namespace Tests\Feature;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Controlled clock: "today" is 21 Nov 2026 (November has 30 days, so daily max = 29).
        $this->travelTo('2026-11-21 10:00:00');
        Storage::fake('public');
    }

    private function garage(string $code, GarageType $type, array $attributes = []): Garage
    {
        return Garage::create(['code' => $code, 'type' => $type, 'daily_price' => 10000, ...$attributes]);
    }

    private function electricRental(Garage $garage, array $overrides = []): array
    {
        return array_merge([
            'vehicle_type' => 'electric_car',
            'plate_number' => 'B 1234 XYZ',
            'vehicle_brand' => 'Hyundai Ioniq 5',
            'battery_capacity' => 60,
            'rental_type' => 'daily',
            'days' => 2,
            'garage_type' => 'electric',
            'garage_id' => $garage->id,
            'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ], $overrides);
    }

    public function test_search_shows_only_available_garages_of_the_chosen_type_with_totals(): void
    {
        $tenant = User::factory()->create();
        $this->garage('E001', GarageType::Electric, ['electricity_price' => 2500]);
        $rented = $this->garage('E002', GarageType::Electric, ['electricity_price' => 2500, 'status' => GarageStatus::Used]);
        $this->garage('C001', GarageType::Car);

        // E002 is really in use: its rental runs until 25 Nov.
        $tenant->rentals()->create([
            'garage_id' => $rented->id, 'vehicle_type' => 'electric_car', 'vehicle_brand' => 'Wuling Air ev',
            'rental_type' => 'daily', 'start_date' => '2026-11-20', 'end_date' => '2026-11-25',
        ]);

        $query = http_build_query([
            'vehicle_type' => 'electric_car', 'plate_number' => 'B 1234 XYZ', 'vehicle_brand' => 'Hyundai Ioniq 5',
            'battery_capacity' => 60, 'rental_type' => 'daily', 'days' => 2, 'garage_type' => 'electric',
        ]);

        $this->actingAs($tenant)
            ->get("/rentals/create?{$query}")
            ->assertOk()
            ->assertSee('E001')
            ->assertDontSee('E002')
            ->assertDontSee('C001')
            ->assertSee('170.000'); // 2 days x 10.000 + 60 kWh x 2.500
    }

    public function test_tenant_rents_an_electric_car_garage(): void
    {
        $garage = $this->garage('E001', GarageType::Electric, ['electricity_price' => 2500]);
        $tenant = User::factory()->create();

        $response = $this->actingAs($tenant)->post('/rentals', $this->electricRental($garage));

        $rental = Rental::first();
        $response->assertRedirect(route('rentals.show', $rental));

        $this->assertSame($tenant->id, $rental->user_id);
        $this->assertSame('2026-11-21', $rental->start_date->toDateString());
        $this->assertSame('2026-11-23', $rental->end_date->toDateString());
        $this->assertSame(170000, $rental->totalPrice());
        Storage::disk('public')->assertExists($rental->payments->first()->proof_path);
        $this->assertSame(GarageStatus::Used, $garage->fresh()->status);
    }

    public function test_monthly_rental_lasts_the_days_of_the_start_month_and_costs_25_days(): void
    {
        $garage = $this->garage('S001', GarageType::Bike, ['daily_price' => 5000]);

        $this->actingAs(User::factory()->create())->post('/rentals', [
            'vehicle_type' => 'bicycle',
            'vehicle_brand' => 'Polygon',
            'rental_type' => 'monthly',
            'garage_type' => 'bike',
            'garage_id' => $garage->id,
            'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $rental = Rental::first();
        $this->assertSame('2026-12-21', $rental->end_date->toDateString()); // 21 Nov + 30 days
        $this->assertNull($rental->plate_number);
        $this->assertSame(125000, $rental->totalPrice()); // 25 x 5.000
    }

    public static function dayCases(): array
    {
        return [
            '29 days (max for November)' => [29, true],
            '30 days' => [30, false],
            '0 days' => [0, false],
            'empty' => [null, false],
        ];
    }

    #[DataProvider('dayCases')]
    public function test_daily_duration_limit(?int $days, bool $accepted): void
    {
        $garage = $this->garage('E001', GarageType::Electric, ['electricity_price' => 2500]);

        $response = $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($garage, ['days' => $days]));

        $accepted ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('days');
    }

    public function test_monthly_rental_cannot_have_days(): void
    {
        $garage = $this->garage('E001', GarageType::Electric, ['electricity_price' => 2500]);

        $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($garage, ['rental_type' => 'monthly', 'days' => 5]))
            ->assertSessionHasErrors('days');
    }

    public function test_bicycle_cannot_use_a_car_garage(): void
    {
        $garage = $this->garage('C001', GarageType::Car);

        $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($garage, ['vehicle_type' => 'bicycle', 'garage_type' => 'car']))
            ->assertSessionHasErrors('garage_type');
    }

    public function test_motorcycle_uses_a_bicycle_garage(): void
    {
        $garage = $this->garage('S001', GarageType::Bike);

        $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($garage, ['vehicle_type' => 'motorcycle', 'garage_type' => 'bike']))
            ->assertSessionHasNoErrors();

        $this->assertSame(GarageStatus::Used, $garage->fresh()->status);
    }

    public function test_motorcycle_needs_a_plate_number(): void
    {
        $garage = $this->garage('S001', GarageType::Bike);

        $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($garage, ['vehicle_type' => 'motorcycle', 'garage_type' => 'bike', 'plate_number' => '']))
            ->assertSessionHasErrors('plate_number');
    }

    public function test_used_garage_cannot_be_rented(): void
    {
        $garage = $this->garage('E001', GarageType::Electric, ['electricity_price' => 2500, 'status' => GarageStatus::Used]);

        $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($garage))
            ->assertSessionHasErrors('garage_id');

        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_garage_of_another_type_cannot_be_chosen(): void
    {
        $carGarage = $this->garage('C001', GarageType::Car);

        $this->actingAs(User::factory()->create())
            ->post('/rentals', $this->electricRental($carGarage)) // says garage_type electric, but C001 is a car garage
            ->assertSessionHasErrors('garage_id');
    }

    public function test_garage_is_free_again_on_the_exclusive_end_date(): void
    {
        $tenant = User::factory()->create();
        $endsToday = $this->garage('S001', GarageType::Bike, ['status' => GarageStatus::Used]);
        $endsTomorrow = $this->garage('S002', GarageType::Bike, ['status' => GarageStatus::Used]);

        foreach ([[$endsToday, '2026-11-21'], [$endsTomorrow, '2026-11-22']] as [$garage, $end]) {
            $tenant->rentals()->create([
                'garage_id' => $garage->id, 'vehicle_type' => 'bicycle', 'vehicle_brand' => 'Polygon',
                'rental_type' => 'daily', 'start_date' => '2026-11-18', 'end_date' => $end,
            ]);
        }

        $query = http_build_query(['vehicle_type' => 'bicycle', 'vehicle_brand' => 'Polygon', 'rental_type' => 'daily', 'days' => 1, 'garage_type' => 'bike']);

        $this->actingAs($tenant)->get("/rentals/create?{$query}")
            ->assertSee('S001')
            ->assertDontSee('S002');

        $this->assertSame(GarageStatus::Available, $endsToday->fresh()->status);
        $this->assertSame(GarageStatus::Used, $endsTomorrow->fresh()->status);
    }

    public function test_tenant_cannot_view_another_tenants_rental(): void
    {
        $garage = $this->garage('S001', GarageType::Bike);
        $rental = User::factory()->create()->rentals()->create([
            'garage_id' => $garage->id, 'vehicle_type' => 'bicycle', 'vehicle_brand' => 'Polygon',
            'rental_type' => 'daily', 'start_date' => '2026-11-21', 'end_date' => '2026-11-22',
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/rentals/{$rental->id}")
            ->assertForbidden();
    }

    public function test_admin_cannot_use_the_rental_form(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/rentals/create')
            ->assertForbidden();
    }
}