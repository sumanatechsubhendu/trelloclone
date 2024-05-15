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

        // If the user is not an admin, redirect or return an error response
        return redirect('/')->with('error', 'You do not have permission to access this resource.');
        // or you can return an error response
        // return response()->json(['error' => 'Unauthorized'], 403);
    }
}
