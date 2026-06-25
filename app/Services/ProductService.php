<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Create a new product.
     *
     * @throws ValidationException
     */
    public function createProduct(array $data): Product
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ];

        Validator::make($data, $rules)->validate();

        return $this->productRepository->create($data);
    }

    /**
     * Get list of products with filters.
     */
    public function getProductList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getList($filters, $perPage);
    }

    /**
     * Get details of a single product.
     *
     * @throws ModelNotFoundException
     */
    public function getProductDetail(int $id): Product
    {
        $product = $this->productRepository->findById($id);

        if (! $product) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$id]);
        }

        return $product;
    }

    /**
     * Update an existing product.
     *
     * @throws ValidationException
     */
    public function updateProduct(int $id, array $data): Product
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|unique:products,sku,'.$id.'|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
        ];

        Validator::make($data, $rules)->validate();

        return $this->productRepository->update($id, $data);
    }

    /**
     * Delete a product (soft delete).
     */
    public function deleteProduct(int $id): bool
    {
        // Check if product exists first
        $this->getProductDetail($id);

        return $this->productRepository->delete($id);
    }
}
