<?php

namespace Tests\Unit;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use PHPUnit\Framework\TestCase;

class EnumTest extends TestCase
{
    public function test_allowed_garage_types_per_vehicle(): void
    {
        $this->assertSame([GarageType::Bike], VehicleType::Bicycle->allowedGarageTypes());
        $this->assertSame([GarageType::Bike], VehicleType::Motorcycle->allowedGarageTypes());
        $this->assertSame([GarageType::Car], VehicleType::Car->allowedGarageTypes());
        $this->assertSame([GarageType::Car, GarageType::Electric], VehicleType::ElectricCar->allowedGarageTypes());
    }

    public function test_only_bicycle_needs_no_plate(): void
    {
        $this->assertFalse(VehicleType::Bicycle->needsPlate());
        $this->assertTrue(VehicleType::Motorcycle->needsPlate());
        $this->assertTrue(VehicleType::Car->needsPlate());
        $this->assertTrue(VehicleType::ElectricCar->needsPlate());
    }

    public function test_garage_code_has_prefix_and_three_digits(): void
    {
        $this->assertSame('S001', GarageType::Bike->code(1));
        $this->assertSame('C010', GarageType::Car->code(10));
        $this->assertSame('E999', GarageType::Electric->code(999));
    }
}
