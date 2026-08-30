<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addon_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->enum('selection_type', ['SINGLE', 'MULTIPLE'])->default('MULTIPLE');
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('min_selection')->default(0);
            $table->unsignedInteger('max_selection')->default(0);
            $table->timestamps();
        });

        Schema::create('addons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('addon_category_id')->constrained('addon_categories')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_addon_categories', function (Blueprint $table): void {
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('addon_category_id')->constrained('addon_categories')->cascadeOnDelete();
            $table->primary(['product_id', 'addon_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addon_categories');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('addon_categories');
    }
};
