<?php

namespace App\Services;

use App\Events\ProductCreated;
use App\DTO\ProductDTO;
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
}