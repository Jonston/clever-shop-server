<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ApplyDiscountTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Apply a discount to products in a specific category. 
        Reduces the price by the specified percentage.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $category = $request->get('category');
        $discountPercent = $request->get('discount_percent');

        $products = Product::where('category', $category)->get();
        
        if ($products->isEmpty()) {
            return Response::text("No products found in category '{$category}'.");
        }

        $updatedCount = 0;
        foreach ($products as $product) {
            $newPrice = $product->price * (1 - $discountPercent / 100);
            $product->update(['price' => round($newPrice, 2)]);
            $updatedCount++;
        }

        return Response::text("Applied {$discountPercent}% discount to {$updatedCount} products in category '{$category}'.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string('The product category to apply discount to'),
            'discount_percent' => $schema->number('The discount percentage (0-100)'),
        ];
    }
}
