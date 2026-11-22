<?php

namespace App\Http\Controllers;

use App\Enums\GarageStatus;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Http\Requests\StoreRentalRequest;
use App\Models\Garage;
use App\Models\Rental;
use App\Services\RentalCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function __construct(private RentalCalculator $calculator) {}

    // Step 1: the form. Step 2 (after "Cari Garasi"): the available garages with their totals.
    public function create(Request $request): View
    {
        $maxDays = $this->calculator->maxDailyDays(today());

        if (! $request->has('vehicle_type')) {
            return view('rentals.create', ['maxDays' => $maxDays, 'details' => null]);
        }

        $vehicle = VehicleType::tryFrom((string) $request->query('vehicle_type'));
        $details = $request->validate(StoreRentalRequest::detailRules($vehicle));

        Garage::releaseExpired();

        $options = Garage::available()
            ->where('type', $details['garage_type'])
            ->orderBy('code')
            ->get()
            ->map(fn (Garage $garage) => ['garage' => $garage, 'total' => $this->totalFor($garage, $details)]);

        return view('rentals.create', [
            'maxDays' => $maxDays,
            'details' => $details,
            'options' => $options,
            'endDate' => $this->calculator->endDate(today(), RentalType::from($details['rental_type']), $details['days'] ?? null),
        ]);
    }

    public function store(StoreRentalRequest $request): RedirectResponse
    {
        $details = $request->validated();
        $garage = Garage::findOrFail($details['garage_id']);
        $type = RentalType::from($details['rental_type']);
        $start = today();

        $rental = DB::transaction(function () use ($request, $details, $garage, $type, $start) {
            // Claim the garage. If another tenant took it a moment ago, nothing is updated.
            $claimed = Garage::whereKey($garage->id)
                ->where('status', GarageStatus::Available)
                ->update(['status' => GarageStatus::Used]);

            if (! $claimed) {
                throw ValidationException::withMessages([
                    'garage_id' => 'Garasi ini baru saja disewa penyewa lain. Silakan pilih garasi lain.',
                ]);
            }

            $rental = $request->user()->rentals()->create([
                'garage_id' => $garage->id,
                'vehicle_type' => $details['vehicle_type'],
                'plate_number' => $details['plate_number'] ?? null,
                'vehicle_brand' => $details['vehicle_brand'],
                'battery_capacity' => $details['battery_capacity'] ?? null,
                'rental_type' => $type,
                'start_date' => $start,
                'end_date' => $this->calculator->endDate($start, $type, $details['days'] ?? null),
            ]);

            $rental->payments()->create([
                'amount' => $this->totalFor($garage, $details),
                'proof_path' => $request->file('proof')->store('proofs', 'public'),
            ]);

            return $rental;
        });

        return redirect()->route('rentals.show', $rental)->with('success', 'Sewa garasi berhasil disimpan.');
    }

    public function show(Request $request, Rental $rental): View
    {
        abort_unless($rental->user_id === $request->user()->id, 403);

        return view('rentals.show', ['rental' => $rental]);
    }

    // Rent price plus the charging cost (only for an electric car in an electric-car garage).
    private function totalFor(Garage $garage, array $details): int
    {
        return $this->calculator->rentPrice($garage->daily_price, RentalType::from($details['rental_type']), $details['days'] ?? null)
            + $this->calculator->chargingCost(
                VehicleType::from($details['vehicle_type']),
                $garage->type,
                $details['battery_capacity'] ?? null,
                $garage->electricity_price,
            );
    }
}
