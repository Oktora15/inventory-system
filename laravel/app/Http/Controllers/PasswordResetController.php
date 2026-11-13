<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);
        $user = User::query()
            ->where('email', $validated['email'])
            ->where('is_active', true)
            ->first();

        if ($user !== null) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with(
            'status',
            'Jika email tersebut terdaftar dan aktif, instruksi pengaturan ulang kata sandi akan dikirim.',
        );
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Kata sandi berhasil diatur. Silakan masuk.');
    }
}
