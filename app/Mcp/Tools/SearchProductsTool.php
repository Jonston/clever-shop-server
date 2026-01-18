<?php

namespace App\Mcp\Tools;

use App\Models\Product;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SearchProductsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Search products by category or name.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $query = Product::query();

        if ($category = $request->get('category')) {
            $query->whereHas('category', fn ($q) => $q->where('name', 'like', "%{$category}%"));
        }

        if ($name = $request->get('name')) {
            $query->where('name', 'like', "%{$name}%");
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return Response::text('No products found.');
        }

        $result = $products->map(fn ($p) => "{$p->id}: {$p->name} - {$p->price}")->join(', ');

        return Response::text("Found products: {$result}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string('Product category to filter by'),
            'name' => $schema->string('Product name to search for'),
        ];
    }
}
