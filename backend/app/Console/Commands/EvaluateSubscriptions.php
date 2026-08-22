<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
use App\Notifications\SubscriptionInvoiceDueNotification;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EvaluateSubscriptions extends Command
{
    protected $signature = 'billing:evaluate-subscriptions';

    protected $description = 'Evaluasi status masa berlaku langganan workspace dan generate invoice tagihan perpanjangan otomatis.';

    public function handle(BillingService $billingService): int
    {
        $now = Carbon::now();
        $this->info('Memulai evaluasi status langganan pengguna...');

        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = User::whereNotNull('subscription_expires_at')
            ->whereNotNull('plan_id')
            ->get();

        $expiringCount = 0;
        $graceCount = 0;
        $suspendedCount = 0;

        foreach ($users as $user) {
            $expiresAt = Carbon::parse($user->subscription_expires_at);

            // skenario 1: H-3 kedaluwarsa (masa aktif tinggal 3 hari atau kurang)
            if ($expiresAt->isFuture() && $now->diffInDays($expiresAt, false) <= 3) {
                if ($user->subscription_status === 'ACTIVE') {
                    $user->update(['subscription_status' => 'EXPIRING_SOON']);
                    $expiringCount++;

                    // cek apakah sudah ada invoice belum lunas
                    $hasUnpaidInvoice = Invoice::where('user_id', $user->id)
                        ->whereIn('status', ['UNPAID', 'PENDING_VERIFICATION'])
                        ->exists();

                    if (! $hasUnpaidInvoice && $user->plan_id) {
                        $invoice = $billingService->createInvoice($user, (string) $user->plan_id, 'MONTHLY');
                        $user->notify(new SubscriptionInvoiceDueNotification($invoice));
                        $this->line("Invoice baru dibuat dan dikirim ke: {$user->email}");
                    }
                }
            }
            // skenario 2: lewat tanggal kedaluwarsa
            elseif ($now->isAfter($expiresAt)) {
                $daysPast = $expiresAt->diffInDays($now);

                if ($daysPast <= 5) {
                    // masih dalam masa tenggang (grace period 5 hari)
                    if ($user->subscription_status !== 'GRACE_PERIOD') {
                        $user->update(['subscription_status' => 'GRACE_PERIOD']);
                        $graceCount++;
                        $this->line("Status user {$user->email} dialihkan ke GRACE_PERIOD");
                    }
                } else {
                    // melewati masa tenggang -> ditangguhkan (SUSPENDED)
                    if ($user->subscription_status !== 'SUSPENDED') {
                        $user->update(['subscription_status' => 'SUSPENDED']);
                        $suspendedCount++;
                        $this->warn("Status user {$user->email} ditangguhkan (SUSPENDED)");
                    }
                }
            }
        }

        $this->info("Evaluasi selesai. EXPIRING_SOON: {$expiringCount}, GRACE_PERIOD: {$graceCount}, SUSPENDED: {$suspendedCount}");

        return Command::SUCCESS;
    }
}
