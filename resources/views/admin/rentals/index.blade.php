@extends('layouts.app')

@section('title', 'Perpanjang Sewa')

@section('content')
    <div class="card">
        <h1 class="mb-4 text-xl font-semibold">Garasi yang Sedang Disewa</h1>

        <table class="w-full text-left text-sm">
            <thead class="border-b text-gray-600">
                <tr>
                    <th class="py-2">Garasi</th>
                    <th>Penyewa</th>
                    <th>Kendaraan</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th>Total Dibayar</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rentals as $rental)
                    <tr class="border-b">
                        <td class="py-2 font-medium">{{ $rental->garage->code }}</td>
                        <td>{{ $rental->user->name }}</td>
                        <td>{{ $rental->vehicle_type->label() }} {{ $rental->plate_number }}</td>
                        <td>{{ $rental->start_date->format('d-m-Y') }}</td>
                        <td>{{ $rental->end_date->format('d-m-Y') }}</td>
                        <td>Rp{{ number_format($rental->totalPrice(), 0, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('admin.rentals.edit', $rental) }}" class="text-blue-600 hover:underline">Perpanjang</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada garasi yang sedang disewa.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection