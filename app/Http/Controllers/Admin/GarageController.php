<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGarageRequest;
use App\Models\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GarageController extends Controller
{
    // The "Buat Garasi" form and the list of all garages are on the same page.
    public function index(): View
    {
        Garage::releaseExpired();

        return view('admin.garages.index', [
            'garages' => Garage::orderBy('code')->paginate(20),
            'types' => GarageType::cases(),
        ]);
    }

    public function store(StoreGarageRequest $request): RedirectResponse
    {
        $type = GarageType::from($request->validated('type'));
        $quantity = (int) $request->validated('quantity');
        $first = Garage::nextNumber($type);
        $last = $first + $quantity - 1;

        DB::transaction(function () use ($request, $type, $first, $last) {
            for ($number = $first; $number <= $last; $number++) {
                Garage::create([
                    'code' => $type->code($number),
                    'type' => $type,
                    'daily_price' => $request->validated('daily_price'),
                    'electricity_price' => $request->validated('electricity_price'),
                ]);
            }
        });

        return redirect()->route('admin.garages.index')
            ->with('success', "{$quantity} garasi berhasil dibuat: {$type->code($first)} sampai {$type->code($last)}.");
    }
}
