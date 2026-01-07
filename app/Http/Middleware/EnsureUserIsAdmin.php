<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 檢查是否登入，且角色是否為 admin
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request);
        }

        // 權限不足，回傳 403 禁止訪問
        abort(403, 'Access Denied: Only admins can access this area.');
    }
}
