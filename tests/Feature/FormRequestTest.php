<?php

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\CheckoutRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('StoreProductRequest rules validate correctly', function () {
    // Save a product to check SKU uniqueness
    Product::create([
        'name' => 'Existing Product',
        'sku' => 'EXISTING-001',
        'price' => 10000,
        'stock' => 5,
    ]);

    $rules = (new StoreProductRequest())->rules();

    // 1. Valid data
    $validData = [
        'name' => 'New Product',
        'sku' => 'NEW-001',
        'price' => 12000.50,
        'stock' => 10,
    ];
    $validator = Validator::make($validData, $rules);
    expect($validator->passes())->toBeTrue();

    // 2. Missing fields
    $invalidData = [
        'name' => '',
        'sku' => '',
        'price' => '',
        'stock' => '',
    ];
    $validator = Validator::make($invalidData, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('name', 'sku', 'price', 'stock');

    // 3. Duplicate SKU
    $duplicateSku = [
        'name' => 'New Product',
        'sku' => 'EXISTING-001',
        'price' => 5000,
        'stock' => 5,
    ];
    $validator = Validator::make($duplicateSku, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('sku');

    // 4. Negative values
    $negativeValues = [
        'name' => 'New Product',
        'sku' => 'NEW-002',
        'price' => -100,
        'stock' => -5,
    ];
    $validator = Validator::make($negativeValues, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('price', 'stock');
});

test('UpdateProductRequest rules validate correctly', function () {
    $product1 = Product::create([
        'name' => 'Product One',
        'sku' => 'SKU-1',
        'price' => 10000,
        'stock' => 10,
    ]);

    $product2 = Product::create([
        'name' => 'Product Two',
        'sku' => 'SKU-2',
        'price' => 20000,
        'stock' => 5,
    ]);

    // Instantiation and mock route binding for route('product')
    $request = new UpdateProductRequest();
    
    // Set route parameter using mock route resolver
    $request->setRouteResolver(function () use ($product1) {
        $mockRoute = Mockery::mock();
        $mockRoute->shouldReceive('parameter')->with('product', Mockery::any())->andReturn($product1->id);
        return $mockRoute;
    });

    $rules = $request->rules();

    // 1. Valid updating own SKU
    $validData = [
        'name' => 'Product One Updated',
        'sku' => 'SKU-1', // keeping same SKU
        'price' => 15000,
        'stock' => 12,
    ];
    $validator = Validator::make($validData, $rules);
    expect($validator->passes())->toBeTrue();

    // 2. Invalid updating to duplicate SKU of product 2
    $duplicateData = [
        'sku' => 'SKU-2',
    ];
    $validator = Validator::make($duplicateData, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('sku');

    // 3. Partial update with negative values
    $negativeData = [
        'price' => -100,
    ];
    $validator = Validator::make($negativeData, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('price');
});

test('CheckoutRequest rules validate correctly', function () {
    $product = Product::create([
        'name' => 'Espresso',
        'sku' => 'KOPI-ESP',
        'price' => 15000,
        'stock' => 20,
    ]);

    $softDeletedProduct = Product::create([
        'name' => 'Old Tea',
        'sku' => 'TEH-OLD',
        'price' => 8000,
        'stock' => 10,
    ]);
    $softDeletedProduct->delete();

    $rules = (new CheckoutRequest())->rules();

    // 1. Valid checkout payload
    $validData = [
        'items' => [
            [
                'product_id' => $product->id,
                'qty' => 2,
            ]
        ]
    ];
    $validator = Validator::make($validData, $rules);
    expect($validator->passes())->toBeTrue();

    // 2. Checkout soft deleted product
    $deletedProductData = [
        'items' => [
            [
                'product_id' => $softDeletedProduct->id,
                'qty' => 1,
            ]
        ]
    ];
    $validator = Validator::make($deletedProductData, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('items.0.product_id');

    // 3. Checkout non-existent product
    $nonExistentData = [
        'items' => [
            [
                'product_id' => 999,
                'qty' => 1,
            ]
        ]
    ];
    $validator = Validator::make($nonExistentData, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('items.0.product_id');

    // 4. Invalid qty (<= 0)
    $invalidQtyData = [
        'items' => [
            [
                'product_id' => $product->id,
                'qty' => 0,
            ]
        ]
    ];
    $validator = Validator::make($invalidQtyData, $rules);
    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->keys())->toContain('items.0.qty');
});
