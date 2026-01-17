<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAll(): Collection
    {
        return Category::all();
    }

    public function find(int $id): ?Category
    {
        return Category::find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): ?Category
    {
        $category = $this->find($id);

        if (! $category) {
            return null;
        }

        $category->update($data);

        return $category;
    }

    public function delete(int $id): bool
    {
        $category = $this->find($id);

        if (! $category) {
            return false;
        }

        return $category->delete();
    }
}
