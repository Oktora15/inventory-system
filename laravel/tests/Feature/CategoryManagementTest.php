<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_category_list_and_create_form_are_available(): void
    {
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Kategori barang');

        $this->get(route('categories.create'))
            ->assertOk()
            ->assertSee('Tambah kategori');
    }

    public function test_category_can_be_created_with_an_optional_description(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Elektronik',
            'description' => 'Perangkat dan aksesori.',
        ])->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Elektronik',
            'description' => 'Perangkat dan aksesori.',
        ]);

        $this->post(route('categories.store'), [
            'name' => 'Peralatan',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Peralatan',
            'description' => null,
        ]);
    }

    public function test_category_name_is_required_and_unique(): void
    {
        Category::create(['name' => 'Elektronik']);

        $this->from(route('categories.create'))
            ->post(route('categories.store'), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->from(route('categories.create'))
            ->post(route('categories.store'), ['name' => 'Elektronik'])
            ->assertSessionHasErrors('name');
    }

    public function test_category_can_be_updated_and_deleted(): void
    {
        $category = Category::create([
            'name' => 'Elektronik',
            'description' => 'Perangkat.',
        ]);

        $this->put(route('categories.update', $category), [
            'name' => 'Elektronik & Audio',
            'description' => 'Perangkat elektronik dan audio.',
        ])->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Elektronik & Audio',
            'description' => 'Perangkat elektronik dan audio.',
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_can_keep_its_name_when_updated(): void
    {
        $category = Category::create(['name' => 'Elektronik']);

        $this->put(route('categories.update', $category), [
            'name' => 'Elektronik',
            'description' => 'Perangkat.',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Elektronik',
        ]);
    }

    public function test_category_in_use_by_products_cannot_be_deleted(): void
    {
        $category = Category::create(['name' => 'Elektronik']);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'ELEK-001',
            'name' => 'Headphone',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'minimum_stock' => 5,
        ]);

        $this->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_list_can_be_searched_by_name_or_description(): void
    {
        Category::create(['name' => 'Elektronik', 'description' => 'Perangkat digital']);
        Category::create(['name' => 'Peralatan', 'description' => 'Alat kerja']);

        $this->get(route('categories.index', ['search' => 'digital']))
            ->assertOk()
            ->assertSee('Elektronik')
            ->assertDontSee('Peralatan');
    }
}
