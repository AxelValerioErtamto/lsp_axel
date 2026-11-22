@extends('layouts.app')

@section('title', 'Garasi')

@section('content')
    <div class="card mb-6">
        <h1 class="mb-4 text-xl font-semibold">Buat Garasi Berkelompok</h1>

        <form method="POST" action="{{ route('admin.garages.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf

            <div>
                <label for="type" class="label">Tipe Garasi</label>
                <select id="type" name="type" class="input" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="quantity" class="label">Jumlah Garasi (maksimal 999)</label>
                <input id="quantity" name="quantity" type="number" min="1" max="999" value="{{ old('quantity') }}" class="input" required>
                @error('quantity') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="daily_price" class="label">Harga Sewa per Hari (Rp)</label>
                <input id="daily_price" name="daily_price" type="number" min="1" value="{{ old('daily_price') }}" class="input" required>
                @error('daily_price') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="electricity_price" class="label">Harga Listrik per kWh (Rp, khusus Garasi Mobil Listrik)</label>
                <input id="electricity_price" name="electricity_price" type="number" min="1" value="{{ old('electricity_price') }}" class="input">
                @error('electricity_price') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="btn">Buat Garasi</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2 class="mb-4 text-lg font-semibold">Daftar Garasi</h2>

        <table class="w-full text-left text-sm">
            <thead class="border-b text-gray-600">
                <tr>
                    <th class="py-2">Kode</th>
                    <th>Tipe</th>
                    <th>Harga / Hari</th>
                    <th>Harga Listrik / kWh</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($garages as $garage)
                    <tr class="border-b">
                        <td class="py-2 font-medium">{{ $garage->code }}</td>
                        <td>{{ $garage->type->label() }}</td>
                        <td>Rp{{ number_format($garage->daily_price, 0, ',', '.') }}</td>
                        <td>{{ $garage->electricity_price ? 'Rp' . number_format($garage->electricity_price, 0, ',', '.') : '-' }}</td>
                        <td>
                            <span class="rounded px-2 py-1 text-xs {{ $garage->status->value === 'used' ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                {{ $garage->status->value }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-500">Belum ada garasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $garages->links() }}</div>
    </div>
@endsection