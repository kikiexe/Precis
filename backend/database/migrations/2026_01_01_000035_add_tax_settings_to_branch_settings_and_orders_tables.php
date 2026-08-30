<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * jalankan migrasi penambahan konfigurasi pajak kustom dinamis
     */
    public function up(): void
    {
        Schema::table('branch_settings', function (Blueprint $table): void {
            $table->boolean('tax_enabled')->default(false)->after('min_overtime_threshold_minutes');
            $table->string('tax_name', 100)->default('PB1')->after('tax_enabled');
            $table->decimal('tax_rate', 5, 2)->default(10.00)->after('tax_name');
            $table->string('tax_type', 20)->default('INCLUSIVE')->after('tax_rate');
            $table->boolean('show_tax_on_receipt')->default(true)->after('tax_type');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('tax_name', 100)->nullable()->after('final_amount');
            $table->decimal('tax_rate', 5, 2)->default(0.00)->after('tax_name');
            $table->string('tax_type', 20)->default('INCLUSIVE')->after('tax_rate');
            $table->decimal('tax_amount', 12, 2)->default(0.00)->after('tax_type');
        });
    }

    /**
     * batalkan migrasi
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['tax_name', 'tax_rate', 'tax_type', 'tax_amount']);
        });

        Schema::table('branch_settings', function (Blueprint $table): void {
            $table->dropColumn(['tax_enabled', 'tax_name', 'tax_rate', 'tax_type', 'show_tax_on_receipt']);
        });
    }
};
