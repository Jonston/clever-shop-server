<?php

namespace App\Mcp\Tools;

use App\DTO\ProductDTO;
use App\Services\ProductService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateProductTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new product in the shop.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $dto = ProductDTO::fromArray([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
            'price' => $request->get('price'),
            'discount' => $request->get('discount'),
            'category_id' => $request->get('category_id'),
        ]);

        $productService = app(ProductService::class);
        $product = $productService->create($dto);

        return Response::text("Product '{$product->name}' created with ID {$product->id}.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string('Product name')->required(),
            'description' => $schema->string('Product description'),
            'price' => $schema->number('Product price')->required(),
            'discount' => $schema->number('Product discount'),
            'category_id' => $schema->integer('Product category ID'),
        ];
    }
}
