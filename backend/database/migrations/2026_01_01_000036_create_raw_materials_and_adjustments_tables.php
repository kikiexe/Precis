<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_materials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->index();
            $table->string('name');
            $table->string('category_id')->nullable();
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('min_stock_alert', 12, 2)->default(5);
            $table->string('unit', 50)->default('liter');
            $table->timestamp('last_adjusted_at')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
        });

        Schema::create('stock_adjustments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id')->index();
            $table->uuid('material_id')->index();
            $table->string('reason', 50);
            $table->decimal('adjusted_amount', 12, 2);
            $table->decimal('resulting_stock', 12, 2);
            $table->text('notes')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('raw_materials')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('raw_materials');
    }
};
