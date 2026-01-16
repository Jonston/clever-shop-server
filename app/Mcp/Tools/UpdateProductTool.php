<?php

namespace App\Mcp\Tools;

use App\DTO\ProductDTO;
use App\Services\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateProductTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing product.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $productService = app(ProductService::class);
        $product = $productService->find($request->get('id'));

        if (!$product) {
            return Response::text('Product not found.');
        }

        $data = array_filter([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'price' => $request->get('price'),
            'category' => $request->get('category'),
        ], fn($value) => $value !== null);

        $dto = ProductDTO::fromArray($data);

        $updatedProduct = $productService->update($product, $dto);

        return Response::text("Product '{$updatedProduct->name}' updated.");
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
            'name' => $schema->string('New product name'),
            'description' => $schema->string('New product description'),
            'price' => $schema->number('New product price'),
            'category' => $schema->string('New product category'),
        ];
    }
}
