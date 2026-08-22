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
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignUuid('pos_terminal_id')->nullable()->constrained('pos_terminals')->nullOnDelete();
            $table->foreignUuid('cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('client_order_id')->unique(); // UUID client-generated for idempotency
            $table->string('order_number', 100);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('final_amount', 12, 2);
            $table->string('payment_method', 50); // CASH, QRIS, TRANSFER
            $table->string('payment_status', 50)->default('PAID'); // PAID, CANCELLED
            $table->timestamps();

            $table->index(['workspace_id', 'branch_id']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
