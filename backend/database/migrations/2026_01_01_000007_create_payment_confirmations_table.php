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
        Schema::create('payment_confirmations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('bank_account_name', 255);
            $table->decimal('transfer_amount', 12, 2);
            $table->string('proof_image_url', 1024);
            $table->foreignUuid('verified_by_superadmin_id')->nullable()->constrained('superadmins')->nullOnDelete();
            $table->timestampTz('verified_at')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_confirmations');
    }
};
