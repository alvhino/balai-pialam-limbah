<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExecutiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Periksa apakah user terautentikasi menggunakan token API
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Ambil user yang sedang login
        $user = Auth::guard('sanctum')->user();

        // Periksa apakah user memiliki peran 'executive'
        if ($user->role !== 'executive') {
            return response()->json(['message' => 'Forbidden: Anda tidak memiliki akses'], 403);
        }

        return $next($request);
    }
}