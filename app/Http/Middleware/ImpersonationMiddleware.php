<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is currently impersonating
        if (Auth::check() && Session::has('impersonating_original_user')) {
            $originalUserId = Session::get('impersonating_original_user');
            $originalUser = User::find($originalUserId);
            
            if ($originalUser) {
                // Share impersonation data with all views
                View::share('isImpersonating', true);
                View::share('originalUser', $originalUser);
                View::share('impersonatedUser', Auth::user());
            } else {
                // Original user not found, clear impersonation session
                Session::forget('impersonating_original_user');
                View::share('isImpersonating', false);
            }
        } else {
            View::share('isImpersonating', false);
        }

        return $next($request);
    }
}
