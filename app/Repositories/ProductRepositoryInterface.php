<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /**
     * Get a paginated list of products with optional filters.
     *
     * @param  array{search?: string}  $filters
     * @return LengthAwarePaginator<Product>
     */
    public function getList(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a product by its ID.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by its ID and lock the row for updates.
     */
    public function findByIdForUpdate(int $id): ?Product;

    /**
     * Create a new product.
     *
     * @param  array{name: string, sku: string, price: float|string, stock: int}  $data
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     *
     * @param  array{name?: string, sku?: string, price?: float|string, stock?: int}  $data
     */
    public function update(int $id, array $data): Product;

    /**
     * Delete a product (soft delete).
     */
    public function delete(int $id): bool;
}
