<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
        }

        $user = Auth::user();

        // Tenants may only log in after an Admin has approved them.
        if (! $user->isApproved()) {
            Auth::logout();

            $message = $user->status === UserStatus::Pending
                ? 'Akun Anda masih menunggu verifikasi Admin.'
                : 'Pendaftaran Anda ditolak oleh Admin.';

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        User::create([
            ...$request->validated(),
            'role' => Role::Tenant,
            'status' => UserStatus::Pending,
        ]);

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil. Silakan tunggu verifikasi Admin sebelum login.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
