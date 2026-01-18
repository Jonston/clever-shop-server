<?php

namespace App\Services;

use App\DTO\ProductDTO;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductsListed;
use App\Events\ProductUpdated;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    public function getAll(): Collection
    {
        return Product::all();
    }

    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Product::paginate($perPage);
    }

    public function find(int $id): ?Product
    {
        return Product::find($id);
    }

    public function create(ProductDTO $dto): Product
    {
        $product = Product::create($dto->toArray());

        broadcast(new ProductCreated($product));

        return $product;
    }

    public function update(Product $product, ProductDTO $dto): Product
    {
        $product->update($dto->toArray());

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    // Methods for AI function calling
    public function listProducts(array $args): array
    {
        $query = Product::query();

        if (isset($args['category'])) {
            $query->where('category', $args['category']);
        }

        $products = $query->get()->toArray();
        broadcast(new ProductsListed($products, $args['category'] ?? null));

        return ['products' => $products];
    }

    public function getProduct(array $args): array
    {
        $product = Product::find($args['id']);

        if (!$product) {
            return ['error' => 'Product not found'];
        }

        return ['product' => $product->toArray()];
    }

    public function createProduct(array $args): array
    {
        $product = Product::create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'price' => $args['price'],
            'category' => $args['category'],
        ]);

        broadcast(new ProductCreated($product));

        return ['product' => $product->toArray(), 'message' => 'Product created successfully'];
    }

    public function updateProduct(array $args): array
    {
        $product = Product::find($args['id']);

        if (!$product) {
            return ['error' => 'Product not found'];
        }

        $product->update(array_filter([
            'name' => $args['name'] ?? null,
            'description' => $args['description'] ?? null,
            'price' => $args['price'] ?? null,
            'category' => $args['category'] ?? null,
        ]));

        $product->refresh();
        broadcast(new ProductUpdated($product));

        return ['product' => $product->toArray(), 'message' => 'Product updated successfully'];
    }

    public function deleteProduct(array $args): array
    {
        $product = Product::find($args['id']);

        if (!$product) {
            return ['error' => 'Product not found'];
        }

        $productId = $product->id;
        $product->delete();

        broadcast(new ProductDeleted($productId));

        return ['message' => 'Product deleted successfully'];
    }
}

