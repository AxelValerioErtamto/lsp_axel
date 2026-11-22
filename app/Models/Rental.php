<?php

namespace App\Models;

use App\Enums\RentalType;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rental extends Model
{
    protected $fillable = [
        'user_id', 'garage_id', 'vehicle_type', 'plate_number', 'vehicle_brand',
        'battery_capacity', 'rental_type', 'start_date', 'end_date',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_type' => VehicleType::class,
            'rental_type' => RentalType::class,
            'battery_capacity' => 'float',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Rentals running today. Periods are [start_date, end_date): the garage is free again ON end_date.
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>', today());
    }

    // Total paid for this rental: the first payment plus every extension.
    public function totalPrice(): int
    {
        return (int) $this->payments->sum('amount');
    }
}
