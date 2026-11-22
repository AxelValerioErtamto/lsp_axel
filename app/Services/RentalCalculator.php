<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class RentalCalculator
{
    // A daily rental may last at most (days in the start month - 1) days.
    public function maxDailyDays(Carbon $start): int
    {
        return $start->daysInMonth - 1;
    }

    // How many days the rental lasts. Monthly = number of days in the start month.
    public function durationDays(Carbon $start, RentalType $type, ?int $days): int
    {
        if ($type === RentalType::Monthly) {
            if ($days !== null) {
                throw new InvalidArgumentException('Sewa bulanan tidak memakai jumlah hari.');
            }

            return $start->daysInMonth;
        }

        if ($days === null || $days < 1 || $days > $this->maxDailyDays($start)) {
            throw new InvalidArgumentException('Jumlah hari sewa harian tidak valid.');
        }

        return $days;
    }

    // Exclusive end date: the garage is free again ON this date.
    public function endDate(Carbon $start, RentalType $type, ?int $days): Carbon
    {
        return $start->copy()->addDays($this->durationDays($start, $type, $days));
    }

    public function rentPrice(int $dailyPrice, RentalType $type, ?int $days): int
    {
        return match ($type) {
            RentalType::Daily => $dailyPrice * $days,
            RentalType::Monthly => $dailyPrice * 25,
        };
    }

    // Only an electric car in an electric-car garage is charged to full.
    public function chargingCost(VehicleType $vehicle, GarageType $garage, ?float $capacity, ?int $pricePerKwh): int
    {
        if ($vehicle !== VehicleType::ElectricCar || $garage !== GarageType::Electric) {
            return 0;
        }

        return (int) round($capacity * $pricePerKwh);
    }

    public function extensionCost(int $dailyPrice, Carbon $oldEnd, Carbon $newEnd): int
    {
        if ($newEnd->lessThanOrEqualTo($oldEnd)) {
            throw new InvalidArgumentException('Tanggal selesai baru harus setelah tanggal selesai lama.');
        }

        return (int) $oldEnd->diffInDays($newEnd) * $dailyPrice;
    }

    // Periods are [start, end). They overlap only when each one starts before the other ends.
    public function overlaps(Carbon $newStart, Carbon $newEnd, Carbon $existingStart, Carbon $existingEnd): bool
    {
        return $newStart->lessThan($existingEnd) && $newEnd->greaterThan($existingStart);
    }
}