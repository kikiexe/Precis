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
        Schema::create('branch_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->decimal('late_penalty_per_minute', 12, 2)->default(1000.00);
            $table->decimal('overtime_pay_per_hour', 12, 2)->default(20000.00);
            $table->unsignedInteger('min_overtime_threshold_minutes')->default(30);
            $table->timestamps();

            $table->index(['workspace_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};
