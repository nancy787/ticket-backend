<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Auth;

class checkIfBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_blocked) {
            $user = Auth::user();
            if ($user && ($user->is_blocked || $user->deleted_at !== null)) {
                Auth::logout();
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Your account has been blocked or deleted.'], 401);
                } else {
                    return redirect()->route('login')->with('error', 'Your account has been blocked or deleted.');
                }
            }
        }

        return $next($request);
    }
}
