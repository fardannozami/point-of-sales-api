<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->has('search') ? ['search' => $request->query('search')] : [];
        $products = $this->productService->getProductList(
            $filters,
            $request->query('per_page', 15)
        );

        $resource = ProductResource::collection($products)->response()->getData(true);

        return $this->successResponse($resource);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return $this->successResponse(
            new ProductResource($product),
            'Product created successfully',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductDetail($id);

        return $this->successResponse(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->validated());

        return $this->successResponse(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return $this->successResponse(null, 'Product deleted successfully');
    }
}
