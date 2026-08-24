<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_members', function (Blueprint $table): void {
            if (! Schema::hasColumn('workspace_members', 'job_title')) {
                $table->string('job_title', 100)->nullable()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspace_members', function (Blueprint $table): void {
            if (Schema::hasColumn('workspace_members', 'job_title')) {
                $table->dropColumn('job_title');
            }
        });
    }
};
