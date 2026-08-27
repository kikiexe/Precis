<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\WorkspaceMember;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Memvalidasi bahwa member saat ini memiliki salah satu hak akses (permission) yang diwajibkan.
     *
     * @param string ...$permissions daftar permission yang salah satunya harus dimiliki
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var WorkspaceMember|null $member */
        $member = $request->attributes->get('current_member');

        if (! $member) {
            return new JsonResponse([
                'message' => 'Konteks keanggotaan workspace tidak ditemukan.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Owner selalu memiliki bypass penuh ke seluruh endpoint
        if ($member->role === 'OWNER') {
            return $next($request);
        }

        // Cek apakah member memiliki setidaknya salah satu permission yang diminta
        foreach ($permissions as $perm) {
            if ($member->hasPermission($perm)) {
                return $next($request);
            }
        }

        return new JsonResponse([
            'message' => 'Peran akun Anda tidak memiliki hak akses yang diperlukan (' . implode(', ', $permissions) . ').',
            'required_permissions' => $permissions,
        ], Response::HTTP_FORBIDDEN);
    }
}
