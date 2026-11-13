<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function landing(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->canManageProducts() ? 'dashboard' : 'products.index');
        }

        return view('landing');
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegistration(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = new User;
        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_CASHIER,
            'is_active' => true,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('products.index')->with('success', 'Pendaftaran berhasil. Akun Kasir Anda aktif.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['is_active'] = true;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi tidak sesuai.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route(Auth::user()->canManageProducts() ? 'dashboard' : 'products.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
