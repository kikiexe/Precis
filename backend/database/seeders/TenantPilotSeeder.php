<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantPilotSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::where('name', 'like', 'Growth%')->first();

        // 1. Owner User Arief
        $owner = User::firstOrCreate(
            ['email' => 'arief@amorecoffee.id'],
            [
                'name' => 'Arief Wicaksono',
                'password' => Hash::make('AmoreOwner2026!'),
                'plan_id' => $plan?->id,
                'max_workspaces' => 5,
                'subscription_status' => 'ACTIVE',
                'subscription_expires_at' => now()->addYear(),
            ]
        );

        // 2. Workspace Amore Coffee Group
        $workspace = Workspace::firstOrCreate(
            ['slug' => 'amore-coffee'],
            [
                'name' => 'Amore Coffee Group',
                'owner_user_id' => $owner->id,
                'status' => 'ACTIVE',
            ]
        );

        // 3. Workspace Membership for Owner
        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
                'user_id' => $owner->id,
            ],
            [
                'branch_id' => null,
                'job_title' => 'Pemilik Usaha',
                'role' => 'OWNER',
                'pin' => Hash::make('9999'),
                'base_salary' => 0.00,
                'is_active' => true,
            ]
        );
    }
}
