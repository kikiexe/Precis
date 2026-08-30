<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\PaymentConfirmation;
use App\Models\SubscriptionPlan;
use App\Models\Superadmin;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class TenantPilotSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::where('name', 'like', 'Growth%')->first();
        $superadmin = Superadmin::first();

        $now = Carbon::now();
        $pilotCreationDate = (clone $now)->subYears(5)->startOfMonth();

        // 1. Owner User: Kiki (Norde Coffee)
        $owner = User::firstOrCreate(
            ['email' => 'kiki@gmail.com'],
            [
                'name' => 'Kiki Norde',
                'password' => Hash::make('123456'),
                'bank_name' => 'BCA',
                'bank_account_number' => '8830192831',
                'bank_account_holder' => 'Kiki Norde',
                'plan_id' => $plan?->id,
                'max_workspaces' => 5,
                'subscription_status' => 'ACTIVE',
                'subscription_expires_at' => (clone $now)->addYear(),
                'email_verified_at' => $pilotCreationDate,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        // 2. Workspace 1: Norde Coffee - Seturan (WS 1)
        $workspaceSeturan = Workspace::firstOrCreate(
            ['slug' => 'norde-coffee'],
            [
                'name' => 'Norde Coffee - Seturan',
                'owner_user_id' => $owner->id,
                'status' => 'ACTIVE',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspaceSeturan->id,
                'user_id' => $owner->id,
            ],
            [
                'branch_id' => null,
                'job_title' => 'Pemilik Usaha',
                'role' => 'OWNER',
                'pin' => Hash::make('999999'),
                'base_salary' => 0.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        // 3. Workspace 2: Norde Coffee - Kaliurang (WS 2)
        $workspaceKaliurang = Workspace::firstOrCreate(
            ['slug' => 'norde-coffee-kaliurang'],
            [
                'name' => 'Norde Coffee - Kaliurang',
                'owner_user_id' => $owner->id,
                'status' => 'ACTIVE',
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        WorkspaceMember::firstOrCreate(
            [
                'workspace_id' => $workspaceKaliurang->id,
                'user_id' => $owner->id,
            ],
            [
                'branch_id' => null,
                'job_title' => 'Pemilik Usaha',
                'role' => 'OWNER',
                'pin' => Hash::make('999999'),
                'base_salary' => 0.00,
                'is_active' => true,
                'created_at' => $pilotCreationDate,
                'updated_at' => $pilotCreationDate,
            ]
        );

        if (app()->environment('testing') || app()->runningUnitTests()) {
            return;
        }

        // 4. Histori 5 Tahun Invoice & Pembayaran Langganan SaaS
        if ($plan && $superadmin) {
            for ($yr = 5; $yr >= 1; $yr--) {
                $invoiceDate = (clone $now)->subYears($yr)->startOfMonth();
                $dueDate = (clone $invoiceDate)->addDays(3);
                $annualPrice = (float) $plan->annual_price;
                $uniqueCode = rand(100, 999);
                $totalAmount = $annualPrice + $uniqueCode;
                $invNumber = sprintf('INV/%s/%04d', $invoiceDate->format('Ymd'), 5 - $yr + 1);

                $invoice = Invoice::firstOrCreate(
                    [
                        'invoice_number' => $invNumber,
                    ],
                    [
                        'user_id' => $owner->id,
                        'amount_base' => $annualPrice,
                        'unique_code' => $uniqueCode,
                        'total_amount' => $totalAmount,
                        'payment_gateway_ref' => 'MANUAL_TRANSFER',
                        'due_date' => $dueDate,
                        'status' => 'VERIFIED',
                        'created_at' => $invoiceDate,
                        'updated_at' => $invoiceDate,
                    ]
                );

                PaymentConfirmation::firstOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                    ],
                    [
                        'user_id' => $owner->id,
                        'bank_account_name' => 'Kiki Norde',
                        'transfer_amount' => $totalAmount,
                        'proof_image_url' => '/seeders/invoices/proof_transfer.webp',
                        'verified_by_superadmin_id' => $superadmin->id,
                        'verified_at' => (clone $invoiceDate)->addHours(4),
                        'created_at' => (clone $invoiceDate)->addHours(2),
                        'updated_at' => (clone $invoiceDate)->addHours(4),
                    ]
                );
            }
        }
    }
}
