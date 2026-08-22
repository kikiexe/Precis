<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::where('slug', 'amore-coffee')->firstOrFail();

        $catalog = [
            'Espresso & Coffee' => [
                ['name' => 'Single Origin Espresso', 'price' => 18000.00],
                ['name' => 'Caffe Americano', 'price' => 22000.00],
                ['name' => 'Amore Signature Latte', 'price' => 28000.00],
                ['name' => 'Caramel Macchiato', 'price' => 32000.00],
                ['name' => 'Vanilla Oat Flat White', 'price' => 34000.00],
            ],
            'Milk & Tea' => [
                ['name' => 'Matcha Kyoto Fusion Latte', 'price' => 30000.00],
                ['name' => 'Earl Grey Milk Tea with Jelly', 'price' => 26000.00],
                ['name' => 'Dark Belgian Chocolate', 'price' => 28000.00],
                ['name' => 'Lemon Sparkler Mint Tea', 'price' => 24000.00],
            ],
            'Pastry & Bakery' => [
                ['name' => 'French Butter Croissant', 'price' => 22000.00],
                ['name' => 'Almond Pain au Chocolat', 'price' => 27000.00],
                ['name' => 'Cinnamon Roll with Glaze', 'price' => 25000.00],
            ],
            'Main Course' => [
                ['name' => 'Amore Special Fried Rice', 'price' => 38000.00],
                ['name' => 'Creamy Truffle Carbonara', 'price' => 45000.00],
                ['name' => 'Crispy Dory Sambal Matah', 'price' => 42000.00],
            ],
        ];

        foreach ($catalog as $categoryName => $products) {
            $category = Category::withoutGlobalScopes()->firstOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'name' => $categoryName,
                ]
            );

            foreach ($products as $item) {
                Product::withoutGlobalScopes()->firstOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'category_id' => $category->id,
                        'name' => $item['name'],
                    ],
                    [
                        'base_price' => $item['price'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
