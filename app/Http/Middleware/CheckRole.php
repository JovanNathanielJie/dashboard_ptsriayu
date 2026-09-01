<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        // Support both single roles dan comma-separated roles
        // Contoh: role:admin_penjualan atau role:direktur_utama,admin_penjualan
        $allowedRoles = [];
        foreach ($roles as $roleStr) {
            $splitRoles = array_map('trim', explode(',', $roleStr));
            $allowedRoles = array_merge($allowedRoles, $splitRoles);
        }
        $allowedRoles = array_filter($allowedRoles, fn ($role) => $role !== '');

        if ($allowedRoles === []) {
            abort(403, 'Role tidak diizinkan untuk mengakses halaman ini.');
        }

        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
