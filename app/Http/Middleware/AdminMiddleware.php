<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated
        if ($request->user() && $request->user()->role === 'admin') {
            return $next($request);
        }
        // or you can return an error response
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to access this resource.',
            'error' => 'Forbidden'
        ], 403);
    }
}
