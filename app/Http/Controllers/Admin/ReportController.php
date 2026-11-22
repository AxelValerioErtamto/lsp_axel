<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GarageType;
use App\Http\Controllers\Controller;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    // Rentals are counted by their start date. Revenue = all payments of those rentals (including extensions).
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'group' => ['nullable', Rule::in(['month', 'day', 'garage_type', 'tenant'])],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $group = $filters['group'] ?? 'month';
        $year = (int) ($filters['year'] ?? today()->year);
        $month = (int) ($filters['month'] ?? today()->month);

        $rows = match ($group) {
            'month' => $this->perMonth($year),
            'day' => $this->perDay($year, $month),
            'garage_type' => $this->perGarageType(),
            'tenant' => $this->perTenant(),
        };

        return view('admin.reports.index', compact('group', 'year', 'month', 'rows'));
    }

    // Every month of the chosen year.
    private function perMonth(int $year): array
    {
        $rentals = Rental::with('payments')->whereYear('start_date', $year)->get();

        return collect(range(1, 12))->map(fn (int $month) => $this->row(
            Carbon::create($year, $month)->locale('id')->isoFormat('MMMM'),
            $rentals->filter(fn (Rental $rental) => $rental->start_date->month === $month),
        ))->all();
    }

    // Every day of the chosen month.
    private function perDay(int $year, int $month): array
    {
        $rentals = Rental::with('payments')
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->get();

        $days = Carbon::create($year, $month)->daysInMonth;

        return collect(range(1, $days))->map(fn (int $day) => $this->row(
            Carbon::create($year, $month, $day)->format('d-m-Y'),
            $rentals->filter(fn (Rental $rental) => $rental->start_date->day === $day),
        ))->all();
    }

    private function perGarageType(): array
    {
        $rentals = Rental::with(['payments', 'garage'])->get();

        return collect(GarageType::cases())->map(fn (GarageType $type) => $this->row(
            $type->label(),
            $rentals->filter(fn (Rental $rental) => $rental->garage->type === $type),
        ))->all();
    }

    private function perTenant(): array
    {
        return Rental::with(['payments', 'user'])->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rentals) => $this->row($rentals->first()->user->name, $rentals))
            ->sortBy('label')
            ->values()
            ->all();
    }

    private function row(string $label, Collection $rentals): array
    {
        return [
            'label' => $label,
            'count' => $rentals->count(),
            'revenue' => $rentals->sum(fn (Rental $rental) => $rental->totalPrice()),
        ];
    }
}
