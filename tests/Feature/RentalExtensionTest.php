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

class RentalExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-21 10:00:00');
        Storage::fake('public');
    }

    // A 3-day rental of a Rp10.000/day car garage: [21 Nov, 24 Nov), already paid Rp30.000.
    private function rental(string $code = 'C001', string $start = '2026-11-21', string $end = '2026-11-24'): Rental
    {
        $garage = Garage::create(['code' => $code, 'type' => GarageType::Car, 'daily_price' => 10000, 'status' => GarageStatus::Used]);

        $rental = User::factory()->create()->rentals()->create([
            'garage_id' => $garage->id, 'vehicle_type' => 'car', 'plate_number' => 'L 1234 AB',
            'vehicle_brand' => 'Toyota Avanza', 'rental_type' => 'daily', 'start_date' => $start, 'end_date' => $end,
        ]);
        $rental->payments()->create(['amount' => 30000, 'proof_path' => 'proofs/first.jpg']);

        return $rental;
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_sees_only_rentals_running_today(): void
    {
        $this->rental('C001');
        $this->rental('C002', '2026-11-18', '2026-11-21'); // ends today, so the garage is already free

        $this->actingAs($this->admin())
            ->get('/admin/rentals')
            ->assertOk()
            ->assertSee('C001')
            ->assertDontSee('C002');
    }

    public function test_admin_sees_the_remaining_cost_before_paying(): void
    {
        $rental = $this->rental();

        $this->actingAs($this->admin())
            ->get("/admin/rentals/{$rental->id}/extend?end_date=2026-11-27")
            ->assertOk()
            ->assertSee('30.000')
            ->assertSee('3 hari'); // 3 extra days x 10.000
    }

    public function test_admin_extends_a_rental(): void
    {
        $rental = $this->rental();

        $this->actingAs($this->admin())
            ->patch("/admin/rentals/{$rental->id}", [
                'end_date' => '2026-11-27',
                'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.rentals.index'));

        $rental->refresh();
        $this->assertSame('2026-11-27', $rental->end_date->toDateString());
        $this->assertCount(2, $rental->payments);
        $this->assertSame(30000, $rental->payments->last()->amount);
        $this->assertSame(60000, $rental->totalPrice());
        Storage::disk('public')->assertExists($rental->payments->last()->proof_path);
    }

    public static function newEndDateCases(): array
    {
        return [
            'one day after the current end' => ['2026-11-25', true],
            'same as the current end' => ['2026-11-24', false],
            'before the current end' => ['2026-11-23', false],
        ];
    }

    #[DataProvider('newEndDateCases')]
    public function test_new_end_date_must_be_after_the_current_one(string $newEnd, bool $accepted): void
    {
        $rental = $this->rental();

        $response = $this->actingAs($this->admin())->patch("/admin/rentals/{$rental->id}", [
            'end_date' => $newEnd,
            'proof' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
        ]);

        $accepted ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('end_date');
    }

    public function test_rental_that_has_ended_cannot_be_extended(): void
    {
        $rental = $this->rental('C001', '2026-11-18', '2026-11-21');

        $this->actingAs($this->admin())
            ->get("/admin/rentals/{$rental->id}/extend")
            ->assertNotFound();
    }

    public function test_tenant_cannot_extend_rentals(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/rentals')
            ->assertForbidden();
    }
}
