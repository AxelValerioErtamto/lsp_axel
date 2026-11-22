@extends('layouts.app')

@section('title', 'Detail Sewa')

@section('content')
    <div class="card max-w-xl">
        <h1 class="mb-4 text-xl font-semibold">Detail Sewa Garasi {{ $rental->garage->code }}</h1>

        <dl class="grid grid-cols-2 gap-y-2 text-sm">
            <dt class="text-gray-600">Garasi</dt>
            <dd>{{ $rental->garage->code }} ({{ $rental->garage->type->label() }})</dd>

            <dt class="text-gray-600">Kendaraan</dt>
            <dd>{{ $rental->vehicle_type->label() }} – {{ $rental->vehicle_brand }}</dd>

            <dt class="text-gray-600">Nomor Plat</dt>
            <dd>{{ $rental->plate_number ?? '-' }}</dd>

            <dt class="text-gray-600">Tipe Sewa</dt>
            <dd>{{ $rental->rental_type->label() }}</dd>

            <dt class="text-gray-600">Tanggal Mulai</dt>
            <dd>{{ $rental->start_date->format('d-m-Y') }}</dd>

            <dt class="text-gray-600">Tanggal Selesai</dt>
            <dd>{{ $rental->end_date->format('d-m-Y') }}</dd>

            <dt class="text-gray-600">Total Biaya</dt>
            <dd class="font-semibold">Rp{{ number_format($rental->totalPrice(), 0, ',', '.') }}</dd>
        </dl>

        @foreach ($rental->payments as $payment)
            <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="mt-4 inline-block text-blue-600 hover:underline">
                Lihat bukti pembayaran
            </a>
        @endforeach
    </div>
@endsection