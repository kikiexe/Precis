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
        Schema::create('shift_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('shift_template_id')->constrained('shift_templates')->cascadeOnDelete();
            $table->foreignUuid('assigned_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('actual_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->boolean('is_swap')->default(false);
            $table->string('swap_status', 50)->default('NONE'); // NONE, PENDING, APPROVED, REJECTED
            $table->foreignUuid('swap_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'date']);
            $table->index(['assigned_user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_assignments');
    }
};
