<?php

use App\Models\Product;

return [

    'driver' => env('SCOUT_DRIVER', 'meilisearch'),

    'prefix' => env('SCOUT_PREFIX', ''),

    'queue' => env('SCOUT_QUEUE', false),

    'after_commit' => false,

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    // Excludes trashed (soft-deleted) products from search results
    // automatically — matches how the rest of the app treats them.
    'soft_delete' => true,

    'identify' => env('SCOUT_IDENTIFY', false),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            Product::class => [
                'filterableAttributes' => [
                    'category_id', 'brand_id', 'is_active', 'is_featured', 'selling_price',
                ],
                'sortableAttributes' => [
                    'selling_price', 'name', 'created_at',
                ],
            ],
        ],
    ],

];
