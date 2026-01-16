<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProductServer;
use App\Mcp\Tools\ApplyDiscountTool;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_discount_tool(): void
    {
        // Создаём продукты
        Product::factory()->create(['category' => 'electronics', 'price' => 100]);
        Product::factory()->create(['category' => 'electronics', 'price' => 200]);
        Product::factory()->create(['category' => 'books', 'price' => 50]);

        // Тестируем tool
        $response = ProductServer::tool(ApplyDiscountTool::class, [
            'category' => 'electronics',
            'discount_percent' => 10,
        ]);

        $response->assertOk()
            ->assertSee('Applied 10% discount to 2 products in category \'electronics\'.');

        // Проверяем, что цены обновились
        $this->assertDatabaseHas('products', ['price' => 90]);  // 100 * 0.9
        $this->assertDatabaseHas('products', ['price' => 180]); // 200 * 0.9
        $this->assertDatabaseHas('products', ['price' => 50]);  // books не изменились
    }

    public function test_apply_discount_to_empty_category(): void
    {
        $response = ProductServer::tool(ApplyDiscountTool::class, [
            'category' => 'nonexistent',
            'discount_percent' => 20,
        ]);

        $response->assertOk()
            ->assertSee('No products found in category \'nonexistent\'.');
    }
}
