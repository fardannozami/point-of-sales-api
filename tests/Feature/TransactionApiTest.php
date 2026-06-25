<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_success()
    {
        $product1 = Product::factory()->create(['price' => 10000, 'stock' => 5]);
        $product2 = Product::factory()->create(['price' => 20000, 'stock' => 3]);

        $payload = [
            'items' => [
                ['product_id' => $product1->id, 'qty' => 2],
                ['product_id' => $product2->id, 'qty' => 1],
            ],
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_amount', 40000);

        $this->assertDatabaseHas('products', ['id' => $product1->id, 'stock' => 3]);
        $this->assertDatabaseHas('products', ['id' => $product2->id, 'stock' => 2]);
    }

    public function test_checkout_fails_product_not_found()
    {
        $payload = [
            'items' => [
                ['product_id' => 999, 'qty' => 1],
            ],
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_checkout_fails_insufficient_stock()
    {
        $product1 = Product::factory()->create(['stock' => 10]);
        $product2 = Product::factory()->create(['stock' => 2]);

        $payload = [
            'items' => [
                ['product_id' => $product1->id, 'qty' => 5],
                ['product_id' => $product2->id, 'qty' => 5],
            ],
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Insufficient stock');

        // Verify stock is not reduced due to atomic rollback
        $this->assertDatabaseHas('products', ['id' => $product1->id, 'stock' => 10]);
        $this->assertDatabaseHas('products', ['id' => $product2->id, 'stock' => 2]);
    }

    public function test_checkout_fails_invalid_qty()
    {
        $product = Product::factory()->create();

        $payload = [
            'items' => [
                ['product_id' => $product->id, 'qty' => 0],
            ],
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_can_list_transactions()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->postJson('/api/transactions', ['items' => [['product_id' => $product->id, 'qty' => 1]]]);
        $this->postJson('/api/transactions', ['items' => [['product_id' => $product->id, 'qty' => 2]]]);

        $response = $this->getJson('/api/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_transaction_detail()
    {
        $product = Product::factory()->create(['stock' => 10]);

        $checkoutResponse = $this->postJson('/api/transactions', ['items' => [['product_id' => $product->id, 'qty' => 1]]]);
        $transactionId = $checkoutResponse->json('data.id');

        $response = $this->getJson('/api/transactions/'.$transactionId);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $transactionId)
            ->assertJsonCount(1, 'data.items');
    }
}
