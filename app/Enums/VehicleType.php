<?php

namespace App\Enums;

enum VehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Motorcycle => 'Sepeda Motor',
            self::Car => 'Mobil',
            self::ElectricCar => 'Mobil Listrik',
        };
    }

    /** @return GarageType[] the garage types this vehicle may be placed in */
    public function allowedGarageTypes(): array
    {
        return match ($this) {
            self::Bicycle, self::Motorcycle => [GarageType::Bike],
            self::Car => [GarageType::Car],
            self::ElectricCar => [GarageType::Car, GarageType::Electric],
        };
    }

    public function needsPlate(): bool
    {
        return $this !== self::Bicycle;
    }
}