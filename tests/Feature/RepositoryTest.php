<?php

use App\Models\Product;
use App\Models\Transaction;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product repository CRUD operations and searching', function () {
    $repository = new ProductRepository;

    // 1. Create Product
    $productData = [
        'name' => 'Kopi Susu Gula Aren',
        'sku' => 'KOPI-001',
        'price' => 18000.00,
        'stock' => 50,
    ];
    $product = $repository->create($productData);

    expect($product)->toBeInstanceOf(Product::class);
    expect($product->name)->toBe('Kopi Susu Gula Aren');
    expect($product->sku)->toBe('KOPI-001');

    // 2. Find Product By ID
    $found = $repository->findById($product->id);
    expect($found)->not->toBeNull();
    expect($found->sku)->toBe('KOPI-001');

    // 3. Update Product
    $updated = $repository->update($product->id, [
        'name' => 'Kopi Susu Gula Aren Premium',
        'stock' => 45,
    ]);
    expect($updated->name)->toBe('Kopi Susu Gula Aren Premium');
    expect($updated->stock)->toBe(45);

    // 4. Get List (with filters)
    // Create another product
    $repository->create([
        'name' => 'Teh Tarik',
        'sku' => 'TEH-001',
        'price' => 12000.00,
        'stock' => 30,
    ]);

    // Search matches 'Kopi'
    $list1 = $repository->getList(['search' => 'Kopi'], 10);
    expect($list1->total())->toBe(1);
    expect($list1->items()[0]->sku)->toBe('KOPI-001');

    // Search matches 'TEH'
    $list2 = $repository->getList(['search' => 'TEH-001'], 10);
    expect($list2->total())->toBe(1);
    expect($list2->items()[0]->name)->toBe('Teh Tarik');

    // 5. Delete Product (Soft delete)
    $deleted = $repository->delete($product->id);
    expect($deleted)->toBeTrue();

    // Verify it is soft-deleted
    expect($repository->findById($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

test('transaction repository operations', function () {
    $productRepository = new ProductRepository;
    $transactionRepository = new TransactionRepository;

    // Create a product
    $product = $productRepository->create([
        'name' => 'Espresso',
        'sku' => 'KOPI-002',
        'price' => 15000.00,
        'stock' => 100,
    ]);

    // Create a transaction
    $transactionData = [
        'transaction_number' => 'TRX-20260625-00001',
        'total_amount' => 30000.00,
        'items' => [
            [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_price' => $product->price,
                'qty' => 2,
                'subtotal' => 30000.00,
            ],
        ],
    ];

    $transaction = $transactionRepository->create($transactionData);

    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->transaction_number)->toBe('TRX-20260625-00001');
    expect($transaction->items)->toHaveCount(1);
    expect($transaction->items[0]->product_name)->toBe('Espresso');
    expect($transaction->items[0]->qty)->toBe(2);

    // Get List
    $list = $transactionRepository->getList(10);
    expect($list->total())->toBe(1);
    expect($list->items()[0]->items)->toHaveCount(1);

    // Find By ID
    $found = $transactionRepository->findById($transaction->id);
    expect($found)->not->toBeNull();
    expect($found->transaction_number)->toBe('TRX-20260625-00001');
    expect($found->items)->toHaveCount(1);
});
