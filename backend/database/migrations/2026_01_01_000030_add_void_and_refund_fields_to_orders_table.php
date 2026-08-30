<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * jalankan migrasi penambahan kolom void dan refund transaksi
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('void_reason')->nullable()->after('payment_status');
            $table->foreignUuid('voided_by_user_id')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by_user_id');

            $table->decimal('refund_amount', 12, 2)->default(0.00)->after('voided_at');
            $table->string('refund_reason')->nullable()->after('refund_amount');
            $table->string('refund_method', 50)->nullable()->after('refund_reason');
            $table->foreignUuid('refunded_in_session_id')->nullable()->after('refund_method')->constrained('pos_sessions')->nullOnDelete();
            $table->foreignUuid('refunded_by_user_id')->nullable()->after('refunded_in_session_id')->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at')->nullable()->after('refunded_by_user_id');
        });
    }

    /**
     * batalkan migrasi
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['voided_by_user_id']);
            $table->dropForeign(['refunded_in_session_id']);
            $table->dropForeign(['refunded_by_user_id']);

            $table->dropColumn([
                'void_reason',
                'voided_by_user_id',
                'voided_at',
                'refund_amount',
                'refund_reason',
                'refund_method',
                'refunded_in_session_id',
                'refunded_by_user_id',
                'refunded_at',
            ]);
        });
    }
};
