@extends('layouts.app')

@use('Illuminate\Support\Carbon')

@section('title', 'Laporan Sewa')

@section('content')
    @php
        $groups = ['month' => 'Per Bulan', 'day' => 'Per Hari', 'garage_type' => 'Per Tipe Garasi', 'tenant' => 'Per Penyewa'];
        $firstColumn = ['month' => 'Bulan', 'day' => 'Tanggal', 'garage_type' => 'Tipe Garasi', 'tenant' => 'Penyewa'][$group];
    @endphp

    <div class="card">
        <h1 class="mb-4 text-xl font-semibold">Laporan Sewa Garasi</h1>

        <form method="GET" action="{{ route('admin.reports.index') }}" class="mb-6 flex flex-wrap items-end gap-4">
            <div>
                <label for="group" class="label">Jenis Laporan</label>
                <select id="group" name="group" class="input">
                    @foreach ($groups as $value => $text)
                        <option value="{{ $value }}" @selected($group === $value)>{{ $text }}</option>
                    @endforeach
                </select>
                @error('group') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="year" class="label">Tahun</label>
                <input id="year" name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="input">
            </div>

            <div>
                <label for="month" class="label">Bulan (untuk laporan per hari)</label>
                <select id="month" name="month" class="input">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($month === $m)>{{ Carbon::create(null, $m)->locale('id')->isoFormat('MMMM') }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn">Tampilkan</button>
        </form>

        <p class="mb-4 text-sm text-gray-600">
            Sewa dihitung berdasarkan tanggal mulai. Pendapatan termasuk pembayaran perpanjangan.
            Laporan per tipe garasi dan per penyewa mencakup semua sewa.
        </p>

        <table class="w-full text-left text-sm">
            <thead class="border-b text-gray-600">
                <tr>
                    <th class="py-2">{{ $firstColumn }}</th>
                    <th>Jumlah Sewa</th>
                    <th>Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="border-b">
                        <td class="py-2">{{ $row['label'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>Rp{{ number_format($row['revenue'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-4 text-center text-gray-500">Belum ada data sewa.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="font-semibold">
                <tr>
                    <td class="py-2">Total</td>
                    <td>{{ collect($rows)->sum('count') }}</td>
                    <td>Rp{{ number_format(collect($rows)->sum('revenue'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@endsection