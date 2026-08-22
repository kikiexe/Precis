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
        Schema::create('attendances', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('shift_assignment_id')->nullable()->constrained('shift_assignments')->nullOnDelete();
            $table->timestampTz('clock_in_time');
            $table->timestampTz('clock_out_time')->nullable();
            $table->string('photo_in_url', 1024);
            $table->string('photo_out_url', 1024)->nullable();
            $table->decimal('lat_in', 10, 8);
            $table->decimal('lng_in', 11, 8);
            $table->decimal('lat_out', 10, 8)->nullable();
            $table->decimal('lng_out', 11, 8)->nullable();
            $table->string('status', 50)->default('APPROVED'); // APPROVED, REJECTED
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->boolean('is_manual_override')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'user_id', 'clock_in_time']);
            $table->index(['workspace_id', 'branch_id', 'clock_in_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
