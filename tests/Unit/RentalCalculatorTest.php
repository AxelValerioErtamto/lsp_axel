<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Services\RentalCalculator;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RentalCalculatorTest extends TestCase
{
    private RentalCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RentalCalculator;
    }

    // ---------- Exclusive end date ----------

    public static function endDateCases(): array
    {
        return [
            'monthly, 20 Feb 2026' => ['2026-02-20', RentalType::Monthly, null, '2026-03-20'],
            'monthly, 20 Feb 2028 (leap year)' => ['2028-02-20', RentalType::Monthly, null, '2028-03-20'],
            'monthly, 10 Jan 2026' => ['2026-01-10', RentalType::Monthly, null, '2026-02-10'],
            'monthly, 31 Jan 2026' => ['2026-01-31', RentalType::Monthly, null, '2026-03-03'],
            'monthly, 10 Apr 2026' => ['2026-04-10', RentalType::Monthly, null, '2026-05-10'],
            'daily 1 day' => ['2026-02-20', RentalType::Daily, 1, '2026-02-21'],
            'daily 5 days' => ['2026-02-20', RentalType::Daily, 5, '2026-02-25'],
            'daily 27 days, crosses into March' => ['2026-02-20', RentalType::Daily, 27, '2026-03-19'],
            'daily 30 days from January' => ['2026-01-10', RentalType::Daily, 30, '2026-02-09'],
        ];
    }

    #[DataProvider('endDateCases')]
    public function test_end_date_is_exclusive(string $start, RentalType $type, ?int $days, string $expected): void
    {
        $end = $this->calculator->endDate(Carbon::parse($start), $type, $days);

        $this->assertSame($expected, $end->toDateString());
    }

    public function test_monthly_from_31_january_adds_31_days_instead_of_one_calendar_month(): void
    {
        $end = $this->calculator->endDate(Carbon::parse('2026-01-31'), RentalType::Monthly, null);

        $this->assertSame('2026-03-03', $end->toDateString());
    }

    // ---------- Daily duration boundaries ----------

    public static function dailyDurationCases(): array
    {
        return [
            'Feb 2026 (28 days): null' => ['2026-02-20', null, false],
            'Feb 2026 (28 days): 0' => ['2026-02-20', 0, false],
            'Feb 2026 (28 days): 1' => ['2026-02-20', 1, true],
            'Feb 2026 (28 days): 26' => ['2026-02-20', 26, true],
            'Feb 2026 (28 days): 27' => ['2026-02-20', 27, true],
            'Feb 2026 (28 days): 28' => ['2026-02-20', 28, false],
            'Feb 2026 (28 days): 29' => ['2026-02-20', 29, false],

            'Feb 2028 (29 days): 0' => ['2028-02-20', 0, false],
            'Feb 2028 (29 days): 1' => ['2028-02-20', 1, true],
            'Feb 2028 (29 days): 27' => ['2028-02-20', 27, true],
            'Feb 2028 (29 days): 28' => ['2028-02-20', 28, true],
            'Feb 2028 (29 days): 29' => ['2028-02-20', 29, false],

            // Starts on 31 Jan: the start month (31 days) sets the limit, even though February is short.
            'Jan 2026 (31 days): 0' => ['2026-01-31', 0, false],
            'Jan 2026 (31 days): 1' => ['2026-01-31', 1, true],
            'Jan 2026 (31 days): 29' => ['2026-01-31', 29, true],
            'Jan 2026 (31 days): 30' => ['2026-01-31', 30, true],
            'Jan 2026 (31 days): 31' => ['2026-01-31', 31, false],

            'Apr 2026 (30 days): 28' => ['2026-04-10', 28, true],
            'Apr 2026 (30 days): 29' => ['2026-04-10', 29, true],
            'Apr 2026 (30 days): 30' => ['2026-04-10', 30, false],
        ];
    }

    #[DataProvider('dailyDurationCases')]
    public function test_daily_duration_boundaries(string $start, ?int $days, bool $accepted): void
    {
        if (! $accepted) {
            $this->expectException(InvalidArgumentException::class);
        }

        $result = $this->calculator->durationDays(Carbon::parse($start), RentalType::Daily, $days);

        $this->assertSame($days, $result);
    }

    public function test_monthly_rental_without_days_is_valid(): void
    {
        $this->assertSame(31, $this->calculator->durationDays(Carbon::parse('2026-01-10'), RentalType::Monthly, null));
    }

    public function test_monthly_rental_with_days_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->durationDays(Carbon::parse('2026-01-10'), RentalType::Monthly, 5);
    }

    // ---------- Pricing ----------

    public static function priceCases(): array
    {
        return [
            'daily 1 day' => [20000, RentalType::Daily, 1, 20000],
            'daily 5 days' => [20000, RentalType::Daily, 5, 100000],
            'daily 27 days' => [20000, RentalType::Daily, 27, 540000],
            'monthly at 20000' => [20000, RentalType::Monthly, null, 500000],
            'monthly at 50000' => [50000, RentalType::Monthly, null, 1250000],
        ];
    }

    #[DataProvider('priceCases')]
    public function test_rent_price(int $dailyPrice, RentalType $type, ?int $days, int $expected): void
    {
        $this->assertSame($expected, $this->calculator->rentPrice($dailyPrice, $type, $days));
    }

    public function test_monthly_price_is_25_times_daily_whatever_the_month_length(): void
    {
        // Start months with 28, 29, 30 and 31 days give different durations but the same price.
        foreach (['2026-02-10' => 28, '2028-02-10' => 29, '2026-04-10' => 30, '2026-01-10' => 31] as $start => $length) {
            $this->assertSame($length, $this->calculator->durationDays(Carbon::parse($start), RentalType::Monthly, null));
            $this->assertSame(500000, $this->calculator->rentPrice(20000, RentalType::Monthly, null));
        }
    }

    // ---------- Charging cost ----------

    public function test_electric_car_in_electric_garage_is_charged_to_full(): void
    {
        $this->assertSame(150000, $this->calculator->chargingCost(VehicleType::ElectricCar, GarageType::Electric, 60, 2500));
    }

    public function test_electric_car_in_normal_car_garage_is_not_charged(): void
    {
        $this->assertSame(0, $this->calculator->chargingCost(VehicleType::ElectricCar, GarageType::Car, 60, null));
    }

    public function test_normal_car_is_not_charged(): void
    {
        $this->assertSame(0, $this->calculator->chargingCost(VehicleType::Car, GarageType::Car, null, null));
    }

    // ---------- Extension ----------

    public function test_extension_cost_is_extra_days_times_daily_price(): void
    {
        $cost = $this->calculator->extensionCost(10000, Carbon::parse('2026-11-24'), Carbon::parse('2026-11-27'));

        $this->assertSame(30000, $cost);
    }

    public function test_extension_to_the_same_or_earlier_date_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->extensionCost(10000, Carbon::parse('2026-11-24'), Carbon::parse('2026-11-24'));
    }

    // ---------- Overlap ----------

    public static function overlapCases(): array
    {
        // Existing booking: [2026-01-01, 2026-01-06)
        return [
            'same period' => ['2026-01-01', '2026-01-06', true],
            'inside' => ['2026-01-03', '2026-01-05', true],
            'starts before existing end' => ['2026-01-05', '2026-01-10', true],
            'starts exactly on existing end' => ['2026-01-06', '2026-01-11', false],
            'ends exactly on existing start' => ['2025-12-28', '2026-01-01', false],
        ];
    }

    #[DataProvider('overlapCases')]
    public function test_overlap_rule(string $newStart, string $newEnd, bool $expected): void
    {
        $result = $this->calculator->overlaps(
            Carbon::parse($newStart), Carbon::parse($newEnd),
            Carbon::parse('2026-01-01'), Carbon::parse('2026-01-06'),
        );

        $this->assertSame($expected, $result);
    }
}
