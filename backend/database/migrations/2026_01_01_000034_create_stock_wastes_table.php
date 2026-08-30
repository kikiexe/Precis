<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * jalankan migrasi tabel pencatatan stock waste / barang rusak terbuang
     */
    public function up(): void
    {
        Schema::create('stock_wastes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('item_name');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 50)->default('Pcs');
            $table->decimal('cost_per_unit', 12, 2)->default(0);
            $table->decimal('total_loss_cost', 12, 2)->default(0);

            // alasan waste / kerusakan / basi / salah buat
            $table->string('reason', 50)->default('SPOILED');
            $table->string('photo_url')->nullable();
            $table->text('notes')->nullable();

            $table->foreignUuid('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // indeks pencarian cepat riwayat waste per cabang dan workspace
            $table->index(['workspace_id', 'branch_id', 'created_at']);
        });
    }

    /**
     * batalkan migrasi
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_wastes');
    }
};
