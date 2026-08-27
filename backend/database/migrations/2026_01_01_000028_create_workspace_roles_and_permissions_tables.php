<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel workspace_roles untuk custom role per tenant
        Schema::create('workspace_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['workspace_id', 'name']);
            $table->index(['workspace_id', 'is_system']);
        });

        // 2. Mapping permission ke role
        Schema::create('workspace_role_permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('role_id')->constrained('workspace_roles')->cascadeOnDelete();
            $table->string('permission', 60);
            $table->timestamps();

            $table->unique(['role_id', 'permission']);
            $table->index(['role_id', 'permission']);
        });

        // 3. Tambah relasi role_id pada workspace_members
        Schema::table('workspace_members', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->nullable()->after('branch_id')->constrained('workspace_roles')->nullOnDelete();
        });

        // 4. Tambah relasi role_id pada workspace_invitations
        Schema::table('workspace_invitations', function (Blueprint $table): void {
            $table->foreignUuid('role_id')->nullable()->after('branch_id')->constrained('workspace_roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::table('workspace_members', function (Blueprint $table): void {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('workspace_role_permissions');
        Schema::dropIfExists('workspace_roles');
    }
};
