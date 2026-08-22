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
        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('role', 50); // OWNER, ADMIN, STAFF
            $table->string('pin')->nullable(); // Hashed PIN for POS / Quick action
            $table->decimal('base_salary', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'user_id']);
            $table->index(['workspace_id', 'branch_id']);
            $table->index(['workspace_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_members');
    }
};
