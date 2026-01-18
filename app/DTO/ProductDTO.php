<?php

namespace App\DTO;

class ProductDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public ?float $discount,
        public ?int $category_id,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            description: $data['description'] ?? null,
            price: isset($data['price']) ? (float) $data['price'] : 0.0,
            discount: isset($data['discount']) ? (float) $data['discount'] : 0.0,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount' => $this->discount,
            'category_id' => $this->category_id,
        ];
    }
}
