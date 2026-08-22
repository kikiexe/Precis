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
        Schema::create('cash_advances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('request_date');
            $table->string('status', 50)->default('PENDING'); // PENDING, APPROVED, DEDUCTED, REJECTED
            $table->foreignUuid('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('deducted_at_payroll_date')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};
