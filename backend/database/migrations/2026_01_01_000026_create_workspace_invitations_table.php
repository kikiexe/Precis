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
        Schema::create('workspace_invitations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('invited_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('job_title', 100);
            $table->string('role', 50); // STAFF, MANAGER, ADMIN
            $table->decimal('base_salary', 12, 2)->default(0.00);
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('status', 20)->default('PENDING'); // PENDING, ACCEPTED, REJECTED, EXPIRED, CANCELLED
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['workspace_id', 'email']);
            $table->index(['token', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
    }
};
