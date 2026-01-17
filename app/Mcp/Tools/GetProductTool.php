<?php

namespace App\Mcp\Tools;

use App\Services\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetProductTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get details of a specific product by ID.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $productService = app(ProductService::class);
        $product = $productService->find($request->get('id'));

        if (! $product) {
            return Response::text('Product not found.');
        }

        return Response::text("Product: {$product->name}, Price: {$product->price}, Category: {$product->category}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer('Product ID')->required(),
        ];
    }
}
