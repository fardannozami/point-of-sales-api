<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Create a new product.
     *
     * @param array $data
     * @return Product
     * @throws \Illuminate\Validation\ValidationException
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
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProductList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->getList($filters, $perPage);
    }

    /**
     * Get details of a single product.
     *
     * @param int $id
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getProductDetail(int $id): Product
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Product::class, [$id]);
        }

        return $product;
    }

    /**
     * Update an existing product.
     *
     * @param int $id
     * @param array $data
     * @return Product
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateProduct(int $id, array $data): Product
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'sometimes|required|string|unique:products,sku,' . $id . '|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
        ];

        Validator::make($data, $rules)->validate();

        return $this->productRepository->update($id, $data);
    }

    /**
     * Delete a product (soft delete).
     *
     * @param int $id
     * @return bool
     */
    public function deleteProduct(int $id): bool
    {
        // Check if product exists first
        $this->getProductDetail($id);

        return $this->productRepository->delete($id);
    }
}
