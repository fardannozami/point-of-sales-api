<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryInterface
{
    /**
     * Get a paginated list of products with optional filters.
     *
     * @param array{search?: string} $filters
     * @param int $perPage
     * @return LengthAwarePaginator<Product>
     */
    public function getList(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a product by its ID.
     *
     * @param int $id
     * @return Product|null
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by its ID and lock the row for updates.
     *
     * @param int $id
     * @return Product|null
     */
    public function findByIdForUpdate(int $id): ?Product;

    /**
     * Create a new product.
     *
     * @param array{name: string, sku: string, price: float|string, stock: int} $data
     * @return Product
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     *
     * @param int $id
     * @param array{name?: string, sku?: string, price?: float|string, stock?: int} $data
     * @return Product
     */
    public function update(int $id, array $data): Product;

    /**
     * Delete a product (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
