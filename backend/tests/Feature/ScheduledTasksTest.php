<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionInvoiceDueNotification;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ScheduledTasksTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->owner = User::where('email', 'arief@amorecoffee.id')->firstOrFail();
        $this->plan = SubscriptionPlan::where('is_active', true)->firstOrFail();
    }

    public function test_evaluate_subscriptions_transitions_to_expiring_soon_and_generates_invoice(): void
    {
        Notification::fake();

        // set masa aktif tersisa 2 hari (masuk rentang H-3)
        $this->owner->update([
            'plan_id' => $this->plan->id,
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => Carbon::now()->addDays(2),
        ]);

        $exitCode = Artisan::call('billing:evaluate-subscriptions');

        $this->assertEquals(0, $exitCode);

        $this->owner->refresh();
        $this->assertEquals('EXPIRING_SOON', $this->owner->subscription_status);

        // pastikan invoice baru otomatis terbentuk
        $this->assertDatabaseHas('invoices', [
            'user_id' => $this->owner->id,
            'status' => 'UNPAID',
        ]);

        Notification::assertSentTo($this->owner, SubscriptionInvoiceDueNotification::class);
    }

    public function test_evaluate_subscriptions_transitions_to_grace_period(): void
    {
        // kedaluwarsa 2 hari yang lalu (dalam masa tenggang 5 hari)
        $this->owner->update([
            'plan_id' => $this->plan->id,
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => Carbon::now()->subDays(2),
        ]);

        $exitCode = Artisan::call('billing:evaluate-subscriptions');

        $this->assertEquals(0, $exitCode);

        $this->owner->refresh();
        $this->assertEquals('GRACE_PERIOD', $this->owner->subscription_status);
    }

    public function test_evaluate_subscriptions_transitions_to_suspended(): void
    {
        // kedaluwarsa 7 hari yang lalu (melebihi masa tenggang 5 hari)
        $this->owner->update([
            'plan_id' => $this->plan->id,
            'subscription_status' => 'ACTIVE',
            'subscription_expires_at' => Carbon::now()->subDays(7),
        ]);

        $exitCode = Artisan::call('billing:evaluate-subscriptions');

        $this->assertEquals(0, $exitCode);

        $this->owner->refresh();
        $this->assertEquals('SUSPENDED', $this->owner->subscription_status);
    }

    public function test_clean_staging_media_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('media:clean-staging');
        $this->assertEquals(0, $exitCode);
    }

    public function test_clean_retention_media_command_executes_successfully(): void
    {
        $exitCode = Artisan::call('media:clean-retention');
        $this->assertEquals(0, $exitCode);
    }
}
