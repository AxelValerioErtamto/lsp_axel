<?php

namespace App\Http\Requests;

use App\Models\Rental;
use Illuminate\Foundation\Http\FormRequest;

class ExtendRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...self::endDateRules($this->route('rental')),
            'proof' => ['required', 'image', 'max:2048'],
        ];
    }

    // The new end date must be after the current one, so the remaining cost is always positive.
    public static function endDateRules(Rental $rental): array
    {
        return [
            'end_date' => ['required', 'date', 'after:'.$rental->end_date->toDateString()],
        ];
    }
}
