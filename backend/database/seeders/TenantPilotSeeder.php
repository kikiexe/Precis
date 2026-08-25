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

        // 1. Owner User: Kiki (Norde Coffee)
        $owner = User::firstOrCreate(
            ['email' => 'kiki@gmail.com'],
            [
                'name' => 'Kiki Norde',
                'password' => Hash::make('123456'),
                'bank_name' => 'BCA',
                'bank_account_number' => '8830192831',
                'bank_account_holder' => 'Kiki Norde',
                'plan_id' => $plan?->id,
                'max_workspaces' => 5,
                'subscription_status' => 'ACTIVE',
                'subscription_expires_at' => now()->addYear(),
                'email_verified_at' => now(),
            ]
        );

        // 2. Workspace 1: Norde Coffee - Seturan #01 (WS 1)
        $workspaceSeturan = Workspace::firstOrCreate(
            ['slug' => 'norde-coffee'],
            [
                'name' => 'Norde Coffee - Seturan #01',
                'owner_user_id' => $owner->id,
                'status' => 'ACTIVE',
            ]
        );

        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
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

        // 3. Workspace 2: Norde Coffee - Kaliurang #02 (WS 2)
        $workspaceKaliurang = Workspace::firstOrCreate(
            ['slug' => 'norde-coffee-kaliurang'],
            [
                'name' => 'Norde Coffee - Kaliurang #02',
                'owner_user_id' => $owner->id,
                'status' => 'ACTIVE',
            ]
        );

        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
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
