<?php

namespace App\Http\Requests;

use App\Enums\GarageStatus;
use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Services\RentalCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Step 2 (saving): the rental details plus the chosen garage and the proof of payment.
    public function rules(): array
    {
        return [
            ...self::detailRules(VehicleType::tryFrom((string) $this->input('vehicle_type'))),
            'garage_id' => [
                'required',
                Rule::exists('garages', 'id')
                    ->where('type', $this->input('garage_type'))
                    ->where('status', GarageStatus::Available->value),
            ],
            'proof' => ['required', 'image', 'max:2048'],
        ];
    }

    // Step 1 (searching): vehicle, rental type and garage type. Also used by RentalController@create.
    public static function detailRules(?VehicleType $vehicle): array
    {
        $allowedGarageTypes = $vehicle
            ? array_map(fn (GarageType $type) => $type->value, $vehicle->allowedGarageTypes())
            : [];

        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            'plate_number' => ['exclude_if:vehicle_type,bicycle', 'required', 'string', 'max:15'],
            'vehicle_brand' => ['required', 'string', 'max:255'],
            'battery_capacity' => ['exclude_unless:vehicle_type,electric_car', 'required', 'numeric', 'min:1', 'max:9999'],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            'days' => [
                'required_if:rental_type,daily',
                'prohibited_if:rental_type,monthly',
                'nullable',
                'integer',
                'min:1',
                'max:' . app(RentalCalculator::class)->maxDailyDays(today()),
            ],
            'garage_type' => ['required', Rule::in($allowedGarageTypes)],
        ];
    }
}