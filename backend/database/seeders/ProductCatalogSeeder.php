<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\AddonCategory;
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
            ],
            'Artisan Tea & Non-Coffee' => [
                ['name' => 'Kyoto Uji Matcha Fusion', 'price' => 28000.00],
                ['name' => 'Earl Grey Peach Iced Tea', 'price' => 24000.00],
                ['name' => 'Dark Belgian Chocolate', 'price' => 27000.00],
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

        $addonCategoriesBlueprint = [
            [
                'name' => 'Pilihan Susu / Dairy Substitute',
                'selection_type' => 'SINGLE',
                'is_required' => false,
                'min_selection' => 0,
                'max_selection' => 1,
                'target_categories' => ['Espresso & Coffee', 'Artisan Tea & Non-Coffee'],
                'addons' => [
                    ['name' => 'Oatside Barista Oat Milk', 'price' => 8000.00],
                    ['name' => 'Almond Milk Premium', 'price' => 10000.00],
                    ['name' => 'Soy Milk Barista', 'price' => 6000.00],
                ],
            ],
            [
                'name' => 'Level Manis / Sweetness',
                'selection_type' => 'SINGLE',
                'is_required' => true,
                'min_selection' => 1,
                'max_selection' => 1,
                'target_categories' => ['Espresso & Coffee', 'Artisan Tea & Non-Coffee'],
                'addons' => [
                    ['name' => 'Normal Sweet (100%)', 'price' => 0.00],
                    ['name' => 'Less Sweet (70%)', 'price' => 0.00],
                    ['name' => 'Low Sweet (30%)', 'price' => 0.00],
                    ['name' => 'No Sugar (0%)', 'price' => 0.00],
                ],
            ],
            [
                'name' => 'Extra Shot & Topping',
                'selection_type' => 'MULTIPLE',
                'is_required' => false,
                'min_selection' => 0,
                'max_selection' => 3,
                'target_categories' => ['Espresso & Coffee', 'Artisan Tea & Non-Coffee'],
                'addons' => [
                    ['name' => 'Extra Espresso Shot', 'price' => 6000.00],
                    ['name' => 'Vanilla Syrup Pump', 'price' => 5000.00],
                    ['name' => 'Caramel Drizzle', 'price' => 4000.00],
                    ['name' => 'Whipped Cream', 'price' => 5000.00],
                ],
            ],
            [
                'name' => 'Pilihan Sambal & Telur',
                'selection_type' => 'MULTIPLE',
                'is_required' => false,
                'min_selection' => 0,
                'max_selection' => 2,
                'target_categories' => ['Main Course & Food'],
                'addons' => [
                    ['name' => 'Telur Ceplok Setengah Matang', 'price' => 5000.00],
                    ['name' => 'Telur Dadar Krispi', 'price' => 5000.00],
                    ['name' => 'Extra Sambal Matah Segar', 'price' => 4000.00],
                ],
            ],
        ];

        foreach ($workspaces as $workspace) {
            $createdAddonCategories = [];

            // 1. buat master addon categories dan addon options
            foreach ($addonCategoriesBlueprint as $blueprint) {
                $addonCategory = AddonCategory::withoutGlobalScopes()->firstOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'name' => $blueprint['name'],
                    ],
                    [
                        'selection_type' => $blueprint['selection_type'],
                        'is_required' => $blueprint['is_required'],
                        'min_selection' => $blueprint['min_selection'],
                        'max_selection' => $blueprint['max_selection'],
                        'created_at' => $pilotCreationDate,
                        'updated_at' => $pilotCreationDate,
                    ]
                );

                foreach ($blueprint['addons'] as $addonOption) {
                    Addon::withoutGlobalScopes()->firstOrCreate(
                        [
                            'workspace_id' => $workspace->id,
                            'addon_category_id' => $addonCategory->id,
                            'name' => $addonOption['name'],
                        ],
                        [
                            'price' => $addonOption['price'],
                            'is_active' => true,
                            'created_at' => $pilotCreationDate,
                            'updated_at' => $pilotCreationDate,
                        ]
                    );
                }

                $createdAddonCategories[] = [
                    'model' => $addonCategory,
                    'target_categories' => $blueprint['target_categories'],
                ];
            }

            // 2. buat master category dan products tepat 3 item per kategori
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

                // hapus produk lama jika lebih dari 3 agar selalu konsisten 3 menu per kategori
                $validProductNames = array_column($products, 'name');
                Product::withoutGlobalScopes()
                    ->where('workspace_id', $workspace->id)
                    ->where('category_id', $category->id)
                    ->whereNotIn('name', $validProductNames)
                    ->delete();

                foreach ($products as $item) {
                    $product = Product::withoutGlobalScopes()->firstOrCreate(
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

                    // hubungkan produk ke modifier categories yang relevan
                    $matchingAddonCatIds = [];
                    foreach ($createdAddonCategories as $addonCatEntry) {
                        if (in_array($categoryName, $addonCatEntry['target_categories'], true)) {
                            $matchingAddonCatIds[] = $addonCatEntry['model']->id;
                        }
                    }

                    if (! empty($matchingAddonCatIds)) {
                        $product->addonCategories()->syncWithoutDetaching($matchingAddonCatIds);
                    }
                }
            }
        }
    }
}
