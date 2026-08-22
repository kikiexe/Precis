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
        Schema::create('pos_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('opened_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_cash', 12, 2);
            $table->decimal('closing_cash_actual', 12, 2)->nullable();
            $table->decimal('closing_cash_expected', 12, 2)->nullable();
            $table->decimal('discrepancy_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('OPEN'); // OPEN, CLOSED
            $table->timestampTz('opened_at');
            $table->timestampTz('closed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
