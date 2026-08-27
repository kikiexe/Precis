<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $pilotCreationDate = (clone $now)->subYears(5)->startOfMonth();

        $workspaces = Workspace::all();

        $catalog = [
            'Espresso & Coffee' => [
                ['name' => 'Norde Signature Aren Latte', 'price' => 22000.00],
                ['name' => 'Butterscotch Sea Salt Latte', 'price' => 30000.00],
                ['name' => 'Caffe Americano Reserve', 'price' => 20000.00],
                ['name' => 'Vanilla Oat Flat White', 'price' => 32000.00],
                ['name' => 'Caramel Macchiato Iced', 'price' => 28000.00],
            ],
            'Artisan Tea & Non-Coffee' => [
                ['name' => 'Kyoto Uji Matcha Fusion', 'price' => 28000.00],
                ['name' => 'Earl Grey Peach Iced Tea', 'price' => 24000.00],
                ['name' => 'Dark Belgian Chocolate', 'price' => 27000.00],
                ['name' => 'Citrus Sparkler Mint Soda', 'price' => 25000.00],
            ],
            'Pastry & Bakery' => [
                ['name' => 'French Butter Croissant', 'price' => 24000.00],
                ['name' => 'Almond Pain au Chocolat', 'price' => 28000.00],
                ['name' => 'Cinnamon Glaze Roll', 'price' => 25000.00],
            ],
            'Main Course & Food' => [
                ['name' => 'Norde Special Fried Rice', 'price' => 36000.00],
                ['name' => 'Creamy Truffle Pasta', 'price' => 42000.00],
                ['name' => 'Crispy Dory Sambal Matah', 'price' => 38000.00],
            ],
        ];

        foreach ($workspaces as $workspace) {
            foreach ($catalog as $categoryName => $products) {
                $category = Category::withoutGlobalScopes()->firstOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'name' => $categoryName,
                    ],
                    [
                        'created_at' => $pilotCreationDate,
                        'updated_at' => $pilotCreationDate,
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
                            'created_at' => $pilotCreationDate,
                            'updated_at' => $pilotCreationDate,
                        ]
                    );
                }
            }
        }
    }
}
