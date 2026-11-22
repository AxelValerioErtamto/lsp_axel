<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGarageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(GarageType::class)],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'daily_price' => ['required', 'integer', 'min:1'],
            // Only used for electric-car garages; ignored for other types.
            'electricity_price' => ['exclude_unless:type,electric', 'required', 'integer', 'min:1'],
        ];
    }

    // Codes have 3 digits, so each garage type can only go up to 999 (e.g. S999).
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $type = GarageType::from($this->input('type'));
                $remaining = 999 - (Garage::nextNumber($type) - 1);

                if ((int) $this->input('quantity') > $remaining) {
                    $validator->errors()->add('quantity', "Nomor kode untuk {$type->label()} hanya tersisa {$remaining}.");
                }
            },
        ];
    }
}
