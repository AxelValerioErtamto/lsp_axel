@extends('layouts.app')

@use('App\Enums\GarageType')
@use('App\Enums\RentalType')
@use('App\Enums\VehicleType')

@section('title', 'Sewa Garasi Baru')

@section('content')
    {{-- Step 1: sent with GET, so the details stay in the URL for step 2. --}}
    <div class="card mb-6">
        <h1 class="mb-4 text-xl font-semibold">Sewa Garasi Baru</h1>

        <form method="GET" action="{{ route('rentals.create') }}" class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="vehicle_type" class="label">Tipe Kendaraan</label>
                <select id="vehicle_type" name="vehicle_type" class="input" required>
                    @foreach (VehicleType::cases() as $vehicle)
                        <option value="{{ $vehicle->value }}" @selected(old('vehicle_type', request('vehicle_type')) === $vehicle->value)>{{ $vehicle->label() }}</option>
                    @endforeach
                </select>
                @error('vehicle_type') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="plate_number" class="label">Nomor Plat (kosongkan untuk sepeda)</label>
                <input id="plate_number" name="plate_number" value="{{ old('plate_number', request('plate_number')) }}" class="input">
                @error('plate_number') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="vehicle_brand" class="label">Merek dan Tipe Kendaraan</label>
                <input id="vehicle_brand" name="vehicle_brand" value="{{ old('vehicle_brand', request('vehicle_brand')) }}" class="input" required>
                @error('vehicle_brand') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="battery_capacity" class="label">Kapasitas Baterai (kWh, khusus mobil listrik)</label>
                <input id="battery_capacity" name="battery_capacity" type="number" step="0.01" min="1" value="{{ old('battery_capacity', request('battery_capacity')) }}" class="input">
                @error('battery_capacity') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental_type" class="label">Tipe Sewa</label>
                <select id="rental_type" name="rental_type" class="input" required>
                    @foreach (RentalType::cases() as $rentalType)
                        <option value="{{ $rentalType->value }}" @selected(old('rental_type', request('rental_type')) === $rentalType->value)>{{ $rentalType->label() }}</option>
                    @endforeach
                </select>
                @error('rental_type') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="days" class="label">Jumlah Hari (khusus harian, maksimal {{ $maxDays }} hari)</label>
                <input id="days" name="days" type="number" min="1" max="{{ $maxDays }}" value="{{ old('days', request('days')) }}" class="input">
                @error('days') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="garage_type" class="label">Tipe Garasi</label>
                <select id="garage_type" name="garage_type" class="input" required>
                    @foreach (GarageType::cases() as $garageType)
                        <option value="{{ $garageType->value }}" @selected(old('garage_type', request('garage_type')) === $garageType->value)>{{ $garageType->label() }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Sepeda & motor: Garasi Sepeda. Mobil: Garasi Mobil. Mobil listrik: Garasi Mobil atau Garasi Mobil Listrik.</p>
                @error('garage_type') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-end">
                <button type="submit" class="btn">Cari Garasi</button>
            </div>
        </form>
    </div>

    {{-- Step 2: available garages with their totals, then upload the proof and save. --}}
    @if ($details)
        <div class="card">
            <h2 class="mb-1 text-lg font-semibold">Pilih Garasi</h2>
            <p class="mb-4 text-sm text-gray-600">
                Mulai {{ today()->format('d-m-Y') }}, selesai {{ $endDate->format('d-m-Y') }}
                (garasi dipakai sampai sebelum tanggal selesai).
            </p>

            @if ($options->isEmpty())
                <p class="text-gray-500">Tidak ada garasi tipe ini yang tersedia hari ini.</p>
            @else
                <form method="POST" action="{{ route('rentals.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @foreach ($details as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-gray-600">
                            <tr>
                                <th class="py-2">Pilih</th>
                                <th>Kode</th>
                                <th>Harga / Hari</th>
                                <th>Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($options as $option)
                                <tr class="border-b">
                                    <td class="py-2">
                                        <input type="radio" name="garage_id" value="{{ $option['garage']->id }}" required @checked($loop->first)>
                                    </td>
                                    <td class="font-medium">{{ $option['garage']->code }}</td>
                                    <td>Rp{{ number_format($option['garage']->daily_price, 0, ',', '.') }}</td>
                                    <td class="font-semibold">Rp{{ number_format($option['total'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @error('garage_id') <p class="error">{{ $message }}</p> @enderror

                    <div>
                        <label for="proof" class="label">Bukti Pelunasan (gambar, maksimal 2 MB)</label>
                        <input id="proof" name="proof" type="file" accept="image/*" class="input" required>
                        @error('proof') <p class="error">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn">Simpan Sewa</button>
                </form>
            @endif
        </div>
    @endif
@endsection