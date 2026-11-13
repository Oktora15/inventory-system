<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_view_landing_login_password_recovery_and_registration_pages(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Kelola persediaan');
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Selamat datang');

        $this->get(route('password.request'))->assertOk();
        $this->get(route('register'))->assertOk()->assertSee('Buat akun Kasir');
    }

    public function test_public_registration_creates_cashier_account_and_logs_them_in(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Kasir Mandiri',
            'email' => 'kasir@example.test',
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
            'role' => User::ROLE_ADMIN,
        ])->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $user = User::query()->where('email', 'kasir@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_CASHIER, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertAuthenticatedAs($user);
        $this->get(route('products.index'))->assertOk();
    }

    public function test_registration_validates_email_and_password_confirmation(): void
    {
        $existing = User::factory()->create(['email' => 'used@example.test']);

        $this->from(route('register'))
            ->post(route('register.store'), [
                'name' => 'Kasir',
                'email' => $existing->email,
                'password' => 'password-aman',
                'password_confirmation' => 'tidak-sama',
            ])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.test',
            'password' => 'password-aman',
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password-aman',
            'remember' => '1',
        ])->assertRedirect(route('products.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.test',
            'password' => 'password-aman',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'user@example.test',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'password-aman',
            'is_active' => false,
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password-aman',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_access_the_app_without_email_verification(): void
    {
        $user = User::factory()->admin()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->get('/email/verify')->assertNotFound();
    }

    public function test_inactive_existing_session_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_cashier_is_limited_to_product_and_stock_out_access(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('products.index'))->assertOk();
        $this->get(route('stock-transactions.index'))->assertOk();
        $this->get(route('scanner.index'))->assertOk();
        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('categories.index'))->assertForbidden();
        $this->get(route('reports.inventory'))->assertForbidden();
        $this->get(route('labels.index'))->assertForbidden();
    }

    public function test_admin_can_open_category_management(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('categories.create'))->assertOk();
    }

    public function test_admin_promotion_command_promotes_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'promote@example.test']);

        $this->artisan('app:promote-user-to-admin', ['email' => $user->email])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_promotion_command_reports_unknown_email(): void
    {
        $this->artisan('app:promote-user-to-admin', ['email' => 'unknown@example.test'])
            ->assertExitCode(1);
    }

    public function test_first_admin_command_creates_admin_credentials_without_email_verification(): void
    {
        $this->artisan('app:create-admin', [
            'name' => 'Admin Pertama',
            'email' => 'admin@example.test',
        ])
            ->expectsQuestion('Kata sandi Admin (minimal 8 karakter)', 'password-admin-aman')
            ->expectsQuestion('Konfirmasi kata sandi', 'password-admin-aman')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->is_active);
        $this->assertNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('password-admin-aman', $admin->password));

        $this->artisan('app:create-admin', [
            'name' => 'Admin Kedua',
            'email' => 'admin2@example.test',
        ])->assertExitCode(1);
        $this->assertDatabaseMissing('users', ['email' => 'admin2@example.test']);
    }
}
