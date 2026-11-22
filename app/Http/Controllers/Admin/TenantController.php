<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    // List of new tenants waiting for verification.
    public function index(): View
    {
        return view('admin.tenants.index', [
            'tenants' => User::where('role', Role::Tenant)
                ->where('status', UserStatus::Pending)
                ->oldest()
                ->get(),
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->status === UserStatus::Pending, 404);

        $user->update(['status' => UserStatus::Approved]);

        return redirect()->route('admin.tenants.index')
            ->with('success', "Pendaftaran {$user->name} diterima.");
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->status === UserStatus::Pending, 404);

        $user->update(['status' => UserStatus::Rejected]);

        return redirect()->route('admin.tenants.index')
            ->with('success', "Pendaftaran {$user->name} ditolak.");
    }
}
