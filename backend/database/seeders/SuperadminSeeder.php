<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Root Superadmin
        Superadmin::firstOrCreate(
            ['email' => 'root@precis.com'],
            [
                'name' => 'Platform Superadmin',
                'password' => Hash::make('PrecisAdmin2026!'),
            ]
        );

        // 2. Master Subscription Plans
        $plans = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Starter (1 Outlet)',
                'max_workspaces' => 1,
                'monthly_price' => 99000.00,
                'annual_price' => 990000.00,
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Growth (5 Outlets)',
                'max_workspaces' => 5,
                'monthly_price' => 299000.00,
                'annual_price' => 2990000.00,
                'is_active' => true,
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Enterprise (Multi Outlets)',
                'max_workspaces' => 20,
                'monthly_price' => 799000.00,
                'annual_price' => 7990000.00,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
