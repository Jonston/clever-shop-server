<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CategoryService;
    }

    public function test_can_get_all_categories(): void
    {
        Category::factory()->count(3)->create();

        $categories = $this->service->getAll();

        $this->assertCount(3, $categories);
    }

    public function test_can_find_category_by_id(): void
    {
        $category = Category::factory()->create();

        $found = $this->service->find($category->id);

        $this->assertNotNull($found);
        $this->assertEquals($category->id, $found->id);
    }

    public function test_can_find_category_by_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'test-slug']);

        $found = $this->service->findBySlug('test-slug');

        $this->assertNotNull($found);
        $this->assertEquals('test-slug', $found->slug);
    }

    public function test_can_create_category(): void
    {
        $data = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test description',
        ];

        $category = $this->service->create($data);

        $this->assertDatabaseHas('categories', $data);
        $this->assertEquals('Test Category', $category->name);
    }

    public function test_can_update_category(): void
    {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->update($category->id, ['name' => 'New Name']);

        $this->assertNotNull($updated);
        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('categories', ['name' => 'New Name']);
    }

    public function test_can_delete_category(): void
    {
        $category = Category::factory()->create();

        $result = $this->service->delete($category->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_returns_null_when_category_not_found(): void
    {
        $found = $this->service->find(999);

        $this->assertNull($found);
    }

    public function test_returns_false_when_deleting_non_existent_category(): void
    {
        $result = $this->service->delete(999);

        $this->assertFalse($result);
    }
}
