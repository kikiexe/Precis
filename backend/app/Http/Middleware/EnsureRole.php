<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\WorkspaceMember;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Menangani request masuk.
     *
     * Memvalidasi bahwa member saat ini memiliki salah satu peran yang diizinkan.
     *
     * @param string ...$roles Peran yang diizinkan (contoh: OWNER, ADMIN, STAFF)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var WorkspaceMember|null $member */
        $member = $request->attributes->get('current_member');

        if (! $member) {
            return new JsonResponse([
                'message' => 'Konteks keanggotaan workspace tidak ditemukan.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! in_array($member->role, $roles, true)) {
            return new JsonResponse([
                'message' => 'Peran akun Anda (' . $member->role . ') tidak memiliki izin untuk mengakses resource ini.',
                'allowed_roles' => $roles,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
