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
        Schema::create('payrolls', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('workspace_member_id')->constrained('workspace_members')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('base_salary', 12, 2)->default(0.00);
            $table->decimal('overtime_pay', 12, 2)->default(0.00);
            $table->decimal('late_penalty', 12, 2)->default(0.00);
            $table->decimal('cash_advance_deduction', 12, 2)->default(0.00);
            $table->decimal('net_salary', 12, 2)->default(0.00);
            $table->string('status', 50)->default('DRAFT'); // DRAFT, DISBURSED
            $table->timestampTz('disbursed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'period_start', 'period_end']);
            $table->index(['user_id', 'period_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
