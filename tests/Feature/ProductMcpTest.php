<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProductServer;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\DeleteProductTool;
use App\Mcp\Tools\GetProductTool;
use App\Mcp\Tools\SearchProductsTool;
use App\Mcp\Tools\UpdateProductTool;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_product_tool(): void
    {
        $category = \App\Models\Category::factory()->create();

        $response = ProductServer::tool(CreateProductTool::class, [
            'name' => 'New Product',
            'price' => 99.99,
            'discount' => 5.0,
            'category_id' => $category->id,
        ]);

        $response->assertOk()
            ->assertSee('Product \'New Product\' created');

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_get_product_tool(): void
    {
        $product = Product::factory()->create();

        $response = ProductServer::tool(GetProductTool::class, [
            'id' => $product->id,
        ]);

        $response->assertOk()
            ->assertSee($product->name);
    }

    public function test_search_products_tool(): void
    {
        $category = \App\Models\Category::factory()->create(['name' => 'fruit']);
        Product::factory()->create(['name' => 'Apple', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Banana', 'category_id' => $category->id]);

        $response = ProductServer::tool(SearchProductsTool::class, [
            'category' => 'fruit',
        ]);

        $response->assertOk()
            ->assertSee('Apple')
            ->assertSee('Banana');
    }

    public function test_update_product_tool(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = ProductServer::tool(UpdateProductTool::class, [
            'id' => $product->id,
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertSee('Product \'New Name\' updated');

        $this->assertDatabaseHas('products', ['name' => 'New Name']);
    }

    public function test_delete_product_tool(): void
    {
        $product = Product::factory()->create();

        $response = ProductServer::tool(DeleteProductTool::class, [
            'id' => $product->id,
        ]);

        $response->assertOk()
            ->assertSee('deleted');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
