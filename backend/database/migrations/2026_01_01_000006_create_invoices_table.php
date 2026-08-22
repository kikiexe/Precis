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
        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('invoice_number', 100)->unique();
            $table->decimal('amount_base', 12, 2);
            $table->unsignedInteger('unique_code');
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_gateway_ref', 255)->nullable();
            $table->timestampTz('due_date');
            $table->string('status', 50)->default('UNPAID');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
