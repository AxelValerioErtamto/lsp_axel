@extends('layouts.app')

@section('title', 'Daftar Penyewa')

@section('content')
    <div class="card mx-auto max-w-md">
        <h1 class="mb-6 text-xl font-semibold">Daftar sebagai Penyewa</h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nik" class="label">NIK (16 digit)</label>
                <input id="nik" name="nik" value="{{ old('nik') }}" class="input" inputmode="numeric" maxlength="16" required>
                @error('nik') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="label">Nama Lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" class="input" required>
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="label">Nomor Telepon</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" class="input" inputmode="numeric" placeholder="08xxxxxxxxxx" required>
                @error('phone') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="input" required>
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="label">Password (minimal 8 karakter)</label>
                <input id="password" name="password" type="password" class="input" required>
                @error('password') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="label">Ulangi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="input" required>
            </div>

            <button type="submit" class="btn w-full">Daftar</button>
        </form>

        <p class="mt-4 text-center text-sm">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Login</a>
        </p>
    </div>
@endsection