<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_can_create_user_and_send_password_setup_link(): void
    {
        Notification::fake();

        $this->post(route('users.store'), [
            'name' => 'Kasir Baru',
            'email' => 'kasir@example.test',
            'role' => User::ROLE_CASHIER,
        ])->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $user = User::query()->where('email', 'kasir@example.test')->firstOrFail();

        $this->assertSame(User::ROLE_CASHIER, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_admin_can_assign_warehouse_role_and_delete_unused_user(): void
    {
        $this->post(route('users.store'), [
            'name' => 'Petugas Gudang',
            'email' => 'warehouse@example.test',
            'role' => User::ROLE_WAREHOUSE,
        ])->assertRedirect(route('users.index'));

        $warehouse = User::query()->where('email', 'warehouse@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_WAREHOUSE, $warehouse->role);

        $this->delete(route('users.destroy', $warehouse))
            ->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $warehouse->id]);
    }

    public function test_admin_cannot_delete_a_user_with_stock_history(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Test']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'TEST-DELETE',
            'name' => 'Test product',
            'purchase_price' => 1,
            'selling_price' => 2,
            'minimum_stock' => 1,
        ]);
        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
            'transaction_date' => now(),
        ]);

        $this->delete(route('users.destroy', $user))
            ->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_cashier_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_can_update_user_email_without_verification_flow(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'old@example.test',
            'email_verified_at' => now(),
        ]);

        $this->put(route('users.update', $user), [
            'name' => 'Nama Baru',
            'email' => 'new@example.test',
            'role' => User::ROLE_ADMIN,
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('new@example.test', $user->email);
        $this->assertNotNull($user->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_admin_cannot_demote_or_deactivate_the_last_admin(): void
    {
        $admin = auth()->user();

        $this->put(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_CASHIER,
        ])->assertSessionHasErrors('role');

        $this->patch(route('users.deactivate', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_deactivate_and_reactivate_another_user(): void
    {
        $user = User::factory()->create();

        $this->patch(route('users.deactivate', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);

        $this->patch(route('users.activate', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_admin_can_view_user_management_pages(): void
    {
        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();
        $this->get(route('users.edit', auth()->user()))->assertOk();
    }
}
