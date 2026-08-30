<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * jalankan migrasi tabel belanja operasional outlet (petty cash)
     */
    public function up(): void
    {
        Schema::create('outlet_purchases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();

            $table->string('item_name');
            $table->string('unit', 50)->default('Pcs');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);

            // kategori belanja operasional
            $table->string('category', 50)->default('OPERASIONAL_TOKO');

            // sumber dana: kas laci (potong settlement) vs reimburse pribadi staf
            $table->string('funding_source', 50)->default('CASH_DRAWER');

            $table->string('receipt_photo_url')->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // indeks pencarian cepat riwayat belanja per sesi dan cabang
            $table->index(['workspace_id', 'branch_id', 'pos_session_id', 'created_at']);
        });
    }

    /**
     * batalkan migrasi
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_purchases');
    }
};
