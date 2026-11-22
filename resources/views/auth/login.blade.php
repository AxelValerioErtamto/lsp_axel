@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="card mx-auto max-w-md">
        <h1 class="mb-6 text-xl font-semibold">Login</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required autofocus>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" class="input" required>
            </div>

            <button type="submit" class="btn w-full">Login</button>
        </form>

        <p class="mt-4 text-center text-sm">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Daftar sebagai penyewa</a>
        </p>
    </div>
@endsection