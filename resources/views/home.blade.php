@extends('layouts.app')

@section('content')
    <div class="card">
        <h1 class="text-xl font-semibold">Halo, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-gray-600">
            Anda login sebagai {{ auth()->user()->isAdmin() ? 'Admin' : 'Penyewa' }}.
        </p>
    </div>
@endsection