<?php

namespace App\Enums;

enum GarageType: string
{
    case Bike = 'bike';
    case Car = 'car';
    case Electric = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::Bike => 'Garasi Sepeda',
            self::Car => 'Garasi Mobil',
            self::Electric => 'Garasi Mobil Listrik',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Bike => 'S',
            self::Car => 'C',
            self::Electric => 'E',
        };
    }

    // Example: GarageType::Car->code(10) returns "C010"
    public function code(int $number): string
    {
        return $this->prefix() . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }
}