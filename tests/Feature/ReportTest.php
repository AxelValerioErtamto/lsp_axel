<?php

namespace Tests\Feature;

use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-21 10:00:00');
        $this->admin = User::factory()->admin()->create();

        $andi = User::factory()->create(['name' => 'Andi']);
        $budi = User::factory()->create(['name' => 'Budi']);
        $car = Garage::create(['code' => 'C001', 'type' => GarageType::Car, 'daily_price' => 10000]);
        $bike = Garage::create(['code' => 'S001', 'type' => GarageType::Bike, 'daily_price' => 5000]);

        // Andi: car garage from 10 Nov, paid 30.000 and later 20.000 for an extension (total 50.000).
        $this->rent($andi, $car, '2026-11-10', [30000, 20000]);
        // Budi: car garage from 21 Nov, paid 30.000.
        $this->rent($budi, $car, '2026-11-21', [30000]);
        // Andi: bicycle garage from 5 Oct, paid 10.000.
        $this->rent($andi, $bike, '2026-10-05', [10000]);
    }

    private function rent(User $tenant, Garage $garage, string $start, array $payments): void
    {
        $rental = $tenant->rentals()->create([
            'garage_id' => $garage->id, 'vehicle_type' => $garage->type === GarageType::Bike ? 'bicycle' : 'car',
            'plate_number' => 'L 1 AB', 'vehicle_brand' => 'Test', 'rental_type' => 'daily',
            'start_date' => $start, 'end_date' => Carbon::parse($start)->addDays(3),
        ]);

        foreach ($payments as $amount) {
            $rental->payments()->create(['amount' => $amount, 'proof_path' => 'proofs/test.jpg']);
        }
    }

    public function test_report_per_month(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/reports?group=month&year=2026')
            ->assertOk()
            ->assertViewHas('rows', function (array $rows) {
                return count($rows) === 12
                    && $rows[9] === ['label' => 'Oktober', 'count' => 1, 'revenue' => 10000]
                    && $rows[10] === ['label' => 'November', 'count' => 2, 'revenue' => 80000];
            });
    }

    public function test_report_per_day(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/reports?group=day&year=2026&month=11')
            ->assertViewHas('rows', function (array $rows) {
                return count($rows) === 30
                    && $rows[9] === ['label' => '10-11-2026', 'count' => 1, 'revenue' => 50000]
                    && $rows[20] === ['label' => '21-11-2026', 'count' => 1, 'revenue' => 30000]
                    && $rows[0]['count'] === 0;
            });
    }

    public function test_report_per_garage_type(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/reports?group=garage_type')
            ->assertViewHas('rows', [
                ['label' => 'Garasi Sepeda', 'count' => 1, 'revenue' => 10000],
                ['label' => 'Garasi Mobil', 'count' => 2, 'revenue' => 80000],
                ['label' => 'Garasi Mobil Listrik', 'count' => 0, 'revenue' => 0],
            ]);
    }

    public function test_report_per_tenant(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/reports?group=tenant')
            ->assertViewHas('rows', [
                ['label' => 'Andi', 'count' => 2, 'revenue' => 60000],
                ['label' => 'Budi', 'count' => 1, 'revenue' => 30000],
            ]);
    }

    public function test_unknown_report_group_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/reports?group=weekly')
            ->assertSessionHasErrors('group');
    }

    public function test_tenant_cannot_see_reports(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/reports')
            ->assertForbidden();
    }
}