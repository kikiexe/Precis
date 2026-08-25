<?php

declare(strict_types=1);

namespace App\Services;

use App\Constants\DomainConstants;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class SuperadminService
{
    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(?string $status = null): Collection
    {
        $query = Invoice::with(['user.ownedWorkspaces', 'confirmation'])
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['UNPAID', 'PENDING_VERIFICATION', 'PAID', 'EXPIRED'], true)) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobalMetrics(): array
    {
        $now = Carbon::now();

        $activeTenants = User::where('subscription_status', 'ACTIVE')->count();
        $gracePeriodTenants = User::where('subscription_status', 'GRACE_PERIOD')->count();
        $suspendedTenants = User::where('subscription_status', 'SUSPENDED')->count();
        $trialTenants = User::where('subscription_status', 'TRIAL')->count();
        $totalTenants = User::count();

        $totalBranches = Branch::count();

        $pendingInvoicesCount = Invoice::where('status', 'PENDING_VERIFICATION')->count();
        $unpaidInvoicesCount = Invoice::where('status', 'UNPAID')->count();
        $paidInvoicesCount = Invoice::where('status', 'PAID')->count();

        $totalRevenue = (float) Invoice::where('status', 'PAID')->sum('total_amount');

        $averagePlanPrice = (float) (SubscriptionPlan::where('is_active', true)->avg('monthly_price') ?? DomainConstants::DEFAULT_MONTHLY_PLAN_PRICE);
        $estimatedMrr = $activeTenants * $averagePlanPrice;
        $estimatedArr = $estimatedMrr * DomainConstants::MONTHS_PER_YEAR;

        return [
            'mrr' => $estimatedMrr,
            'arr' => $estimatedArr,
            'total_revenue' => $totalRevenue,
            'tenants' => [
                'total' => $totalTenants,
                'active' => $activeTenants,
                'grace_period' => $gracePeriodTenants,
                'suspended' => $suspendedTenants,
                'trial' => $trialTenants,
            ],
            'total_branches' => $totalBranches,
            'invoices' => [
                'pending' => $pendingInvoicesCount,
                'unpaid' => $unpaidInvoicesCount,
                'paid' => $paidInvoicesCount,
            ],
            'timestamp' => $now->toIso8601String(),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function getTenantsDirectory(?string $status = null): Collection
    {
        $query = User::with(['ownedWorkspaces.branches'])
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['ACTIVE', 'GRACE_PERIOD', 'SUSPENDED', 'TRIAL'], true)) {
            $query->where('subscription_status', $status);
        }

        return $query->get();
    }

    public function updateTenantSubscriptionStatus(string $id, string $status): User
    {
        /** @var User $user */
        $user = User::findOrFail($id);
        $user->update([
            'subscription_status' => $status,
        ]);

        return $user;
    }

    public function extendTenantSubscription(string $id, int $days = DomainConstants::DEFAULT_SUBSCRIPTION_EXTENSION_DAYS): User
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        $now = Carbon::now();
        $currentExpiry = $user->subscription_expires_at ? Carbon::parse($user->subscription_expires_at) : null;
        $baseDate = ($currentExpiry && $currentExpiry->isFuture()) ? $currentExpiry : $now;
        $newExpiry = $baseDate->copy()->addDays($days);

        $user->update([
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => $newExpiry,
        ]);

        return $user;
    }
}
