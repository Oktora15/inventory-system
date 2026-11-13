<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::query()->withCount('stockTransactions')->orderBy('name')->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_CASHIER])],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = new User;
            $user->forceFill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'password' => str()->random(64),
                'is_active' => true,
            ])->save();

            return $user;
        });

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors([
                'email' => 'Akun tersimpan, tetapi tautan pengaturan kata sandi tidak dapat dikirim. Coba kirim ulang dari daftar pengguna.',
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna dibuat. Tautan pengaturan kata sandi telah dikirim ke email.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_CASHIER])],
        ]);

        if ($user->is($request->user()) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan role akun Admin yang sedang digunakan.']);
        }

        if (
            $user->is_active
            && $user->role === User::ROLE_ADMIN
            && $validated['role'] !== User::ROLE_ADMIN
            && ! $this->hasAnotherActiveAdmin($user)
        ) {
            return back()->withErrors(['role' => 'Aplikasi harus memiliki setidaknya satu Admin aktif.']);
        }

        $user->forceFill($validated)->save();

        return redirect()->route('users.index')->with(
            'success',
            'Data pengguna berhasil diperbarui.',
        );
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        if ($user->is_active && $user->role === User::ROLE_ADMIN && ! $this->hasAnotherActiveAdmin($user)) {
            return back()->withErrors(['user' => 'Aplikasi harus memiliki setidaknya satu Admin aktif.']);
        }

        $user->forceFill(['is_active' => false])->save();

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dinonaktifkan.');
    }

    public function activate(User $user): RedirectResponse
    {
        $user->forceFill(['is_active' => true])->save();

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil diaktifkan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun sendiri.']);
        }

        if ($user->stockTransactions()->exists()) {
            return back()->withErrors([
                'user' => 'Akun memiliki riwayat transaksi dan tidak dapat dihapus. Nonaktifkan akun sebagai gantinya.',
            ]);
        }

        if ($user->is_active && $user->role === User::ROLE_ADMIN && ! $this->hasAnotherActiveAdmin($user)) {
            return back()->withErrors(['user' => 'Aplikasi harus memiliki setidaknya satu Admin aktif.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Akun pengguna berhasil dihapus.');
    }

    public function sendPasswordResetLink(User $user): RedirectResponse
    {
        if (! $user->is_active) {
            return back()->withErrors(['user' => 'Aktifkan akun sebelum mengirim tautan pengaturan kata sandi.']);
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['user' => 'Tautan pengaturan kata sandi tidak dapat dikirim. Coba lagi nanti.']);
        }

        return redirect()->route('users.index')->with('success', 'Tautan pengaturan kata sandi berhasil dikirim.');
    }

    private function hasAnotherActiveAdmin(User $user): bool
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->exists();
    }
}
