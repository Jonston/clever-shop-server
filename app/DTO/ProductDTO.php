<?php

namespace App\DTO;

class ProductDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public ?string $category,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : 0.0,
            category: $data['category'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category,
        ];
    }
}