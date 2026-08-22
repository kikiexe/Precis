<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveWorkspaceContext
{
    /**
     * handle request masuk
     *
     * memvalidasi header X-Workspace-Id dan mengikat konteks workspace serta member
     * ke dalam atribut request untuk menjamin eksekusi yang aman di laravel octane
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return new JsonResponse([
                'message' => 'Autentikasi diperlukan untuk mengakses resource ini.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $workspaceId = $request->header('X-Workspace-Id') ?? $request->route('workspace_id');

        if (! $workspaceId) {
            return new JsonResponse([
                'message' => 'Header X-Workspace-Id wajib disertakan.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $workspace = Workspace::where('id', $workspaceId)->first();
        if (! $workspace) {
            return new JsonResponse([
                'message' => 'Workspace tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $member = WorkspaceMember::withoutGlobalScopes()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $member) {
            return new JsonResponse([
                'message' => 'Anda tidak memiliki akses ke workspace ini.',
            ], Response::HTTP_FORBIDDEN);
        }

        // simpan konteks yang teresolusi di atribut request (bercakup request, aman di frankenphp octane)
        $request->attributes->set('current_workspace_id', $workspace->id);
        $request->attributes->set('current_workspace', $workspace);
        $request->attributes->set('current_member', $member);

        return $next($request);
    }
}
