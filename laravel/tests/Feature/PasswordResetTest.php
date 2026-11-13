<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_password_reset_request_is_generic_and_only_notifies_active_accounts(): void
    {
        Notification::fake();
        $activeUser = User::factory()->create(['email' => 'active@example.test']);
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.test',
            'is_active' => false,
        ]);

        $this->post(route('password.email'), ['email' => $activeUser->email])
            ->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => $inactiveUser->email])
            ->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'unknown@example.test'])
            ->assertSessionHas('status');

        Notification::assertSentTo($activeUser, ResetPassword::class);
        Notification::assertNotSentTo($inactiveUser, ResetPassword::class);
    }

    public function test_valid_reset_token_changes_password_without_email_verification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['email' => 'reset@example.test']);

        $this->post(route('password.email'), ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return true;
            },
        );
        $this->assertIsString($token);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru-aman',
            'password_confirmation' => 'password-baru-aman',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('password-baru-aman', $user->password));
        $this->assertNull($user->email_verified_at);
        $this->actingAs($user)->get(route('products.index'))->assertOk();
    }

    public function test_reset_form_is_available_and_invalid_passwords_are_rejected(): void
    {
        $token = 'sample-token';

        $this->get(route('password.reset', ['token' => $token, 'email' => 'user@example.test']))
            ->assertOk()
            ->assertSee('Atur kata sandi baru');

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => 'user@example.test',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['password']);
    }
}
