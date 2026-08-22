<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\PosTerminal;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenAuth
{
    /**
     * handles request masuk buat rute terminal POS.
     *
     * validate header X-Device-Token terhadap hash token di tabel pos_terminals.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceToken = $request->header('X-Device-Token');

        if (! $deviceToken) {
            return new JsonResponse([
                'message' => 'Header X-Device-Token wajib disertakan untuk terminal POS.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokenHash = hash('sha256', $deviceToken);

        $terminal = PosTerminal::withoutGlobalScopes()
            ->with(['workspace', 'branch'])
            ->where('device_token_hash', $tokenHash)
            ->first();

        if (! $terminal) {
            return new JsonResponse([
                'message' => 'Device token terminal POS tidak valid atau tidak terdaftar.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $terminal->is_active) {
            return new JsonResponse([
                'message' => 'Terminal POS ini telah dinonaktifkan oleh administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        // ikat konteks ke atribut request (aman di FrankenPHP Octane)
        $request->attributes->set('current_workspace_id', $terminal->workspace_id);
        $request->attributes->set('current_branch_id', $terminal->branch_id);
        $request->attributes->set('current_pos_terminal', $terminal);

        return $next($request);
    }
}
