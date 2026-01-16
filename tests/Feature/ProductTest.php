<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_product(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'category' => 'Test Category',
        ];

        $response = $this->postJson('/products', $data);

        $response->assertStatus(201)
            ->assertJson($data);

        $this->assertDatabaseHas('products', $data);
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson($product->toArray());
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();

        $data = [
            'name' => 'Updated Product',
            'price' => 149.99,
        ];

        $response = $this->putJson("/products/{$product->id}", $data);

        $response->assertStatus(200)
            ->assertJson($data);

        $this->assertDatabaseHas('products', $data);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Product deleted']);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/products/999');

        $response->assertStatus(404);
    }

    public function test_validates_required_fields_on_create(): void
    {
        $response = $this->postJson('/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price']);
    }

    public function test_validates_price_is_numeric(): void
    {
        $data = [
            'name' => 'Test Product',
            'price' => 'not-a-number',
        ];

        $response = $this->postJson('/products', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }
}
