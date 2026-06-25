<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * @inheritDoc
     */
    public function findByIdForUpdate(int $id): ?Product
    {
        return Product::lockForUpdate()->find($id);
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product;
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): bool
    {
        $product = Product::findOrFail($id);
        return (bool) $product->delete();
    }
}
