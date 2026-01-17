<?php

return [
    'functions' => [
        [
            'name' => 'list_products',
            'description' => 'Get a list of all products. Can filter by category.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'category' => [
                        'type' => 'string',
                        'description' => 'Optional category to filter products',
                    ],
                ],
            ],
        ],
        [
            'name' => 'get_product',
            'description' => 'Get details of a specific product by ID',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'number',
                        'description' => 'Product ID',
                    ],
                ],
                'required' => ['id'],
            ],
        ],
        [
            'name' => 'create_product',
            'description' => 'Create a new product',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'description' => 'Product name',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Product description',
                    ],
                    'price' => [
                        'type' => 'number',
                        'description' => 'Product price',
                    ],
                    'category' => [
                        'type' => 'string',
                        'description' => 'Product category',
                    ],
                ],
                'required' => ['name', 'price', 'category'],
            ],
        ],
        [
            'name' => 'update_product',
            'description' => 'Update an existing product',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'number',
                        'description' => 'Product ID',
                    ],
                    'name' => [
                        'type' => 'string',
                        'description' => 'Product name',
                    ],
                    'description' => [
                        'type' => 'string',
                        'description' => 'Product description',
                    ],
                    'price' => [
                        'type' => 'number',
                        'description' => 'Product price',
                    ],
                    'category' => [
                        'type' => 'string',
                        'description' => 'Product category',
                    ],
                ],
                'required' => ['id'],
            ],
        ],
        [
            'name' => 'delete_product',
            'description' => 'Delete a product by ID',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'number',
                        'description' => 'Product ID to delete',
                    ],
                ],
                'required' => ['id'],
            ],
        ],
    ],
];
