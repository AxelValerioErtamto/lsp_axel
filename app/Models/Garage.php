<?php

namespace App\Models;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Garage extends Model
{
    protected $fillable = ['code', 'type', 'daily_price', 'electricity_price', 'status'];

    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'status' => GarageStatus::class,
        ];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    // Usage: Garage::available()->get()
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', GarageStatus::Available);
    }

    // Next number for a new garage code of this type, e.g. 13 when S012 exists.
    public static function nextNumber(GarageType $type): int
    {
        $lastCode = static::where('type', $type)->max('code');

        return $lastCode ? (int) substr($lastCode, 1) + 1 : 1;
    }

    // Sets used garages back to available once their rental has ended (end_date is exclusive).
    public static function releaseExpired(): int
    {
        return static::where('status', GarageStatus::Used)
            ->whereDoesntHave('rentals', fn (Builder $query) => $query->whereDate('end_date', '>', today()))
            ->update(['status' => GarageStatus::Available]);
    }
}