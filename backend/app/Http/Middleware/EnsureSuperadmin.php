<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Superadmin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperadmin
{
    /**
     * pastikan pengguna yang terautentikasi adalah instance Superadmin yang sah
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Superadmin) {
            return new JsonResponse([
                'message' => 'Akses ditolak. Endpoint ini memerlukan otorisasi Superadmin.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
