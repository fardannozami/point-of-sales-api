<?php

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Transaction;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Services\ProductService;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('product service business rules and operations', function () {
    $productRepo = new ProductRepository;
    $service = new ProductService($productRepo);

    // 1. Create product with invalid sku (empty)
    try {
        $service->createProduct([
            'name' => 'Valid Name',
            'sku' => '',
            'price' => 100,
            'stock' => 10,
        ]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('sku');
    }

    // 2. Create product with negative price
    try {
        $service->createProduct([
            'name' => 'Valid Name',
            'sku' => 'SKU-001',
            'price' => -5,
            'stock' => 10,
        ]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('price');
    }

    // 3. Create product successfully
    $product = $service->createProduct([
        'name' => 'Valid Name',
        'sku' => 'SKU-001',
        'price' => 15000,
        'stock' => 10,
    ]);
    expect($product->sku)->toBe('SKU-001');

    // 4. Duplicate SKU check
    try {
        $service->createProduct([
            'name' => 'Another Name',
            'sku' => 'SKU-001',
            'price' => 10000,
            'stock' => 5,
        ]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('sku');
    }

    // 5. Get detail & fail check
    $found = $service->getProductDetail($product->id);
    expect($found->id)->toBe($product->id);

    try {
        $service->getProductDetail(999);
        $this->fail('Expected ModelNotFoundException was not thrown.');
    } catch (ModelNotFoundException $e) {
        expect($e->getMessage())->toContain('No query results for model');
    }

    // 6. Update successfully & fail validation checks
    $updated = $service->updateProduct($product->id, ['price' => 20000]);
    expect($updated->price)->toBe('20000.00');

    try {
        $service->updateProduct($product->id, ['stock' => -10]);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('stock');
    }

    // 7. Delete product
    $deleted = $service->deleteProduct($product->id);
    expect($deleted)->toBeTrue();

    // Check soft-deleted item is not found
    try {
        $service->getProductDetail($product->id);
        $this->fail('Expected ModelNotFoundException was not thrown.');
    } catch (ModelNotFoundException $e) {
        // success, soft deleted
    }
});

test('transaction service checkout and stock deduction', function () {
    $productRepo = new ProductRepository;
    $transactionRepo = new TransactionRepository;
    $productService = new ProductService($productRepo);
    $transactionService = new TransactionService($transactionRepo, $productRepo);

    // Create products
    $p1 = $productService->createProduct([
        'name' => 'Kopi',
        'sku' => 'SKU-KOP',
        'price' => 15000,
        'stock' => 10,
    ]);

    $p2 = $productService->createProduct([
        'name' => 'Roti',
        'sku' => 'SKU-ROT',
        'price' => 10000,
        'stock' => 5,
    ]);

    // 1. Successful checkout
    $checkoutData = [
        'items' => [
            ['product_id' => $p1->id, 'qty' => 2],
            ['product_id' => $p2->id, 'qty' => 1],
        ],
    ];

    $transaction = $transactionService->checkout($checkoutData);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->total_amount)->toBe('40000.00'); // (15000*2) + (10000*1)
    expect($transaction->transaction_number)->toMatch('/^TRX-\d{8}-\d{5}$/');

    // Assert stock is deducted correctly
    expect($p1->fresh()->stock)->toBe(8);
    expect($p2->fresh()->stock)->toBe(4);

    // 2. Checkout failing due to insufficient stock
    $failData = [
        'items' => [
            ['product_id' => $p2->id, 'qty' => 10], // Requesting 10 but only 4 left
        ],
    ];

    try {
        $transactionService->checkout($failData);
        $this->fail('Expected InsufficientStockException was not thrown.');
    } catch (InsufficientStockException $e) {
        expect($e->getMessage())->toBe('Insufficient stock');
        expect($e->getErrors())->toHaveKey($p2->id);
        expect($e->getErrors()[$p2->id][0])->toBe('Available stock is 4');
    }

    // Verify stock was NOT deducted (rollback check)
    expect($p2->fresh()->stock)->toBe(4);

    // 3. Checkout with invalid qty (<= 0)
    $invalidQtyData = [
        'items' => [
            ['product_id' => $p1->id, 'qty' => 0],
        ],
    ];

    try {
        $transactionService->checkout($invalidQtyData);
        $this->fail('Expected ValidationException was not thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('items.0.qty');
    }

    // 4. Get list & detail
    $list = $transactionService->getTransactionList(10);
    expect($list->total())->toBe(1);

    $detail = $transactionService->getTransactionDetail($transaction->id);
    expect($detail->transaction_number)->toBe($transaction->transaction_number);
    expect($detail->items)->toHaveCount(2);
});
