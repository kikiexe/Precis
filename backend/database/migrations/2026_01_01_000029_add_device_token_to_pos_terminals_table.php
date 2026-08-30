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
        if (Schema::hasColumn('pos_terminals', 'device_token')) {
            Schema::table('pos_terminals', function (Blueprint $table): void {
                $table->dropColumn('device_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Plaintext device tokens are prohibited in the database schema.
    }
};
