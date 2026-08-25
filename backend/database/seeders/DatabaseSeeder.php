<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's baseline database with 3-month rich dataset for Norde Coffee.
     */
    public function run(): void
    {
        $this->call([
            SuperadminSeeder::class,
            TenantPilotSeeder::class,
            BranchSeeder::class,
            ProductCatalogSeeder::class,
            StaffAndShiftSeeder::class,
            SalesHistorySeeder::class,
        ]);
    }
}
