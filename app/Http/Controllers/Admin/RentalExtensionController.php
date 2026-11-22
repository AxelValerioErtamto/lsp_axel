<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExtendRentalRequest;
use App\Models\Rental;
use App\Services\RentalCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RentalExtensionController extends Controller
{
    public function __construct(private RentalCalculator $calculator) {}

    // Garages that are being rented today.
    public function index(): View
    {
        return view('admin.rentals.index', [
            'rentals' => Rental::active()->with(['garage', 'user', 'payments'])->orderBy('end_date')->get(),
        ]);
    }

    // Step 1: enter a new end date. Step 2 (after "Hitung Sisa Biaya"): see the cost and upload the proof.
    public function edit(Request $request, Rental $rental): View
    {
        abort_unless($rental->end_date->isAfter(today()), 404);

        $newEnd = null;
        $cost = null;

        if ($request->has('end_date')) {
            $request->validate(ExtendRentalRequest::endDateRules($rental));

            $newEnd = Carbon::parse($request->query('end_date'));
            $cost = $this->calculator->extensionCost($rental->garage->daily_price, $rental->end_date, $newEnd);
        }

        return view('admin.rentals.extend', ['rental' => $rental, 'newEnd' => $newEnd, 'cost' => $cost]);
    }

    public function update(ExtendRentalRequest $request, Rental $rental): RedirectResponse
    {
        abort_unless($rental->end_date->isAfter(today()), 404);

        $newEnd = Carbon::parse($request->validated('end_date'));
        $cost = $this->calculator->extensionCost($rental->garage->daily_price, $rental->end_date, $newEnd);

        DB::transaction(function () use ($request, $rental, $newEnd, $cost) {
            $rental->update(['end_date' => $newEnd]);

            $rental->payments()->create([
                'amount' => $cost,
                'proof_path' => $request->file('proof')->store('proofs', 'public'),
            ]);
        });

        return redirect()->route('admin.rentals.index')
            ->with('success', "Sewa garasi {$rental->garage->code} diperpanjang sampai {$newEnd->format('d-m-Y')}.");
    }
}
