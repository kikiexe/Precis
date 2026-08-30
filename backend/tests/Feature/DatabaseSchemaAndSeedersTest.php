<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PosTerminal;
use App\Models\Product;
use App\Models\ShiftAssignment;
use App\Models\ShiftTemplate;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaAndSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_migrations_and_master_seeders_execute_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verify Superadmin & Plans
        $this->assertDatabaseHas('superadmins', [
            'email' => 'root@precis.com',
        ]);
        $this->assertEquals(3, SubscriptionPlan::count());

        // 2. Verify Pilot Tenant & Workspace
        $owner = User::where('email', 'kiki@gmail.com')->first();
        $this->assertNotNull($owner);

        $workspace = Workspace::where('slug', 'norde-coffee')->first();
        $this->assertNotNull($workspace);
        $this->assertEquals($owner->id, $workspace->owner_user_id);

        $ownerMember = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $owner->id)
            ->first();
        $this->assertNotNull($ownerMember);
        $this->assertEquals('OWNER', $ownerMember->role);

        // 3. Verify Branches, Settings, and POS Terminals
        $branches = Branch::withoutGlobalScopes()->where('workspace_id', $workspace->id)->get();
        $this->assertCount(1, $branches);

        foreach ($branches as $branch) {
            $this->assertDatabaseHas('branch_settings', [
                'workspace_id' => $workspace->id,
                'branch_id' => $branch->id,
            ]);
            $this->assertDatabaseHas('pos_terminals', [
                'workspace_id' => $workspace->id,
                'branch_id' => $branch->id,
            ]);
        }

        // 4. Verify Staff & Shifts
        $this->assertDatabaseHas('users', ['email' => 'paundra@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'ami@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'hani@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'ajril@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'rama@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'kia@gmail.com']);

        $totalMembersWS1 = WorkspaceMember::withoutGlobalScopes()->where('workspace_id', $workspace->id)->count();
        $this->assertEquals(4, $totalMembersWS1); // Owner + Manager + 2 Cashiers (Ami & Hani)

        $templates = ShiftTemplate::withoutGlobalScopes()->where('workspace_id', $workspace->id)->count();
        $this->assertEquals(2, $templates); // Pagi & Sore

        $assignments = ShiftAssignment::withoutGlobalScopes()->where('workspace_id', $workspace->id)->count();
        $this->assertEquals(1, $assignments);

        // 5. Verify Categories and Products
        $categories = Category::withoutGlobalScopes()->where('workspace_id', $workspace->id)->count();
        $this->assertEquals(4, $categories);

        $products = Product::withoutGlobalScopes()->where('workspace_id', $workspace->id)->count();
        $this->assertEquals(12, $products); // 4 kategori x 3 menu = 12 produk
    }
}
