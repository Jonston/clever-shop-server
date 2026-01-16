<?php

namespace App\Http\Controllers;

use App\DTO\ProductDTO;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = $this->productService->getPaginated();

        return response()->json($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $dto = ProductDTO::fromArray($request->validated());
        $product = $this->productService->create($dto);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $dto = ProductDTO::fromArray($request->validated());
        $updatedProduct = $this->productService->update($product, $dto);

        return response()->json($updatedProduct);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = $this->productService->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted']);
    }
}
