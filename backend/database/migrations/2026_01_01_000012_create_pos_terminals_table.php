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
        Schema::create('pos_terminals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('terminal_name', 255);
            $table->string('device_token_hash', 255)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_terminals');
    }
};
