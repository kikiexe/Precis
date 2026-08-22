<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Evaluasi status masa berlaku langganan workspace owner.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Workspace|null $workspace */
        $workspace = $request->attributes->get('current_workspace');

        if (! $workspace) {
            $workspaceId = $request->header('X-Workspace-Id') ?? $request->attributes->get('current_workspace_id');
            if ($workspaceId) {
                $workspace = Workspace::withoutGlobalScopes()->find($workspaceId);
            }
        }

        if (! $workspace) {
            return $next($request);
        }

        /** @var User|null $owner */
        $owner = $workspace->owner ?? User::find($workspace->owner_user_id);

        if (! $owner) {
            return $next($request);
        }

        $now = Carbon::now();

        // 1. Jika status langsung SUSPENDED
        if ($owner->subscription_status === 'SUSPENDED') {
            return new JsonResponse([
                'message' => 'Layanan workspace ini telah ditangguhkan (SUSPENDED). Silakan lakukan pembayaran perpanjangan langganan.',
                'subscription_status' => 'SUSPENDED',
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        // 2. Evaluasi tanggal kedaluwarsa jika ada
        if ($owner->subscription_expires_at) {
            $expiresAt = Carbon::parse($owner->subscription_expires_at);

            if ($now->isAfter($expiresAt)) {
                $daysPast = $expiresAt->diffInDays($now);

                // Masa tenggang (GRACE_PERIOD) adalah 5 hari
                if ($daysPast > 5) {
                    $owner->update(['subscription_status' => 'SUSPENDED']);

                    return new JsonResponse([
                        'message' => 'Masa tenggang pembayaran telah berakhir dan langganan telah ditangguhkan (SUSPENDED).',
                        'subscription_status' => 'SUSPENDED',
                    ], Response::HTTP_PAYMENT_REQUIRED);
                }

                // Masih dalam masa tenggang
                $response = $next($request);
                $response->headers->set('X-Subscription-Warning', 'GRACE_PERIOD');

                return $response;
            }
        }

        return $next($request);
    }
}
