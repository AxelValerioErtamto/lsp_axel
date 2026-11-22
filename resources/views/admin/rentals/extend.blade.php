@extends('layouts.app')

@section('title', 'Perpanjang Sewa')

@section('content')
    <div class="card max-w-xl">
        <h1 class="mb-4 text-xl font-semibold">Perpanjang Sewa Garasi {{ $rental->garage->code }}</h1>

        <dl class="mb-6 grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-600">Penyewa</dt>
            <dd>{{ $rental->user->name }}</dd>

            <dt class="text-gray-600">Harga per Hari</dt>
            <dd>Rp{{ number_format($rental->garage->daily_price, 0, ',', '.') }}</dd>

            <dt class="text-gray-600">Tanggal Selesai Saat Ini</dt>
            <dd>{{ $rental->end_date->format('d-m-Y') }}</dd>
        </dl>

        {{-- Step 1: choose the new end date. --}}
        <form method="GET" action="{{ route('admin.rentals.edit', $rental) }}" class="mb-6 flex items-end gap-4">
            <div class="flex-1">
                <label for="end_date" class="label">Tanggal Selesai Baru</label>
                <input id="end_date" name="end_date" type="date"
                       min="{{ $rental->end_date->copy()->addDay()->toDateString() }}"
                       value="{{ old('end_date', request('end_date')) }}" class="input" required>
                @error('end_date') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn">Hitung Sisa Biaya</button>
        </form>

        {{-- Step 2: show the remaining cost, then upload the proof and save. --}}
        @if ($cost !== null)
            <form method="POST" action="{{ route('admin.rentals.update', $rental) }}" enctype="multipart/form-data" class="space-y-4 border-t pt-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="end_date" value="{{ $newEnd->toDateString() }}">

                <p>
                    Sisa biaya yang harus dibayar:
                    <span class="font-semibold">Rp{{ number_format($cost, 0, ',', '.') }}</span>
                    ({{ (int) $rental->end_date->diffInDays($newEnd) }} hari × Rp{{ number_format($rental->garage->daily_price, 0, ',', '.') }})
                </p>

                <div>
                    <label for="proof" class="label">Bukti Pembayaran (gambar, maksimal 2 MB)</label>
                    <input id="proof" name="proof" type="file" accept="image/*" class="input" required>
                    @error('proof') <p class="error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn">Simpan Perpanjangan</button>
            </form>
        @endif
    </div>
@endsection