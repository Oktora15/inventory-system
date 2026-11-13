<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->create([
            'email' => 'profile@example.test',
            'password' => 'password-lama',
        ]));
    }

    public function test_user_can_update_profile_and_change_password_with_current_password(): void
    {
        $user = auth()->user();

        $this->get(route('profile.edit'))->assertOk()->assertSee('Profil saya');
        $this->put(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
            'current_password' => 'password-lama',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('baru@example.test', $user->email);
        $this->assertTrue(Hash::check('password-baru', $user->password));
    }

    public function test_profile_requires_correct_current_password_and_valid_new_password(): void
    {
        $user = auth()->user();

        $this->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'name' => 'Tidak tersimpan',
                'email' => $user->email,
                'current_password' => 'salah',
                'password' => 'short',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertSessionHasErrors(['current_password', 'password']);

        $this->assertSame($user->name, $user->fresh()->name);
    }
}
