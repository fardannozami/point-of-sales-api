<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_product()
    {
        $payload = [
            'name' => 'Kopi Susu',
            'price' => 15000,
            'stock' => 10,
            'sku' => 'SKU-001',
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Kopi Susu');

        $this->assertDatabaseHas('products', ['sku' => 'SKU-001']);
    }

    public function test_create_product_validation_fails()
    {
        $payload = [
            'name' => '',
            'price' => -100,
        ];

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_can_list_products()
    {
        Product::factory()->count(20)->create();

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_can_show_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/products/'.$product->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_show_product_not_found()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $payload = [
            'name' => 'New Name',
            'price' => 20000,
            'stock' => 50,
            'sku' => $product->sku,
        ];

        $response = $this->putJson('/api/products/'.$product->id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson('/api/products/'.$product->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
