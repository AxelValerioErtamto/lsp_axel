@extends('layouts.app')

@section('title', 'Verifikasi Pendaftar')

@section('content')
    <div class="card">
        <h1 class="mb-4 text-xl font-semibold">Verifikasi Pendaftar Baru</h1>

        <table class="w-full text-left text-sm">
            <thead class="border-b text-gray-600">
                <tr>
                    <th class="py-2">NIK</th>
                    <th>Nama</th>
                    <th>Telepon</th>
                    <th>Email</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tenants as $tenant)
                    <tr class="border-b">
                        <td class="py-2 font-mono">{{ $tenant->nik }}</td>
                        <td>{{ $tenant->name }}</td>
                        <td>{{ $tenant->phone }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td>{{ $tenant->created_at->format('d-m-Y') }}</td>
                        <td class="flex gap-2 py-2">
                            <form method="POST" action="{{ route('admin.tenants.approve', $tenant) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700">Terima</button>
                            </form>
                            <form method="POST" action="{{ route('admin.tenants.reject', $tenant) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700">Tolak</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-gray-500">Tidak ada pendaftar yang menunggu verifikasi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection