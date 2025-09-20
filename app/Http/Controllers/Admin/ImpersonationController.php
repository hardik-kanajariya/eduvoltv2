<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        
        // Check if the current user has permission to impersonate
        if (!$currentUser->hasRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized to impersonate users.');
        }

        // Don't allow impersonating super admins
        if ($user->hasRole('super_admin')) {
            return back()->withErrors(['impersonation' => 'Cannot impersonate super administrators.']);
        }

        // Don't allow impersonating yourself
        if (Auth::id() === $user->id) {
            return back()->withErrors(['impersonation' => 'Cannot impersonate yourself.']);
        }

        // Store the original user ID in session
        Session::put('impersonating_original_user', Auth::id());
        
        // Log the impersonation start
        $this->logImpersonation('started', $currentUser, $user);

        // Login as the target user
        Auth::login($user);

        return redirect('/dashboard')->with('success', "Now impersonating {$user->name}");
    }

    /**
     * Stop impersonating and return to original user.
     */
    public function stopImpersonating(): RedirectResponse
    {
        if (!Session::has('impersonating_original_user')) {
            return redirect('/dashboard')->withErrors(['impersonation' => 'Not currently impersonating anyone.']);
        }

        $originalUserId = Session::get('impersonating_original_user');
        $impersonatedUser = Auth::user();
        
        // Find the original user
        $originalUser = User::find($originalUserId);
        if (!$originalUser) {
            Session::forget('impersonating_original_user');
            return redirect('/login')->withErrors(['impersonation' => 'Original user not found.']);
        }

        // Log the impersonation stop
        $this->logImpersonation('stopped', $originalUser, $impersonatedUser);

        // Clear the impersonation session
        Session::forget('impersonating_original_user');

        // Login as the original user
        Auth::login($originalUser);

        return redirect('/dashboard')->with('success', 'Stopped impersonating user.');
    }

    /**
     * Check if currently impersonating.
     */
    public function isImpersonating(): bool
    {
        return Session::has('impersonating_original_user');
    }

    /**
     * Get the original user if impersonating.
     */
    public function getOriginalUser(): ?User
    {
        if (!$this->isImpersonating()) {
            return null;
        }

        return User::find(Session::get('impersonating_original_user'));
    }

    /**
     * Log impersonation activities for audit purposes.
     */
    private function logImpersonation(string $action, User $originalUser, User $targetUser): void
    {
        Log::info("User impersonation {$action}", [
            'action' => $action,
            'original_user_id' => $originalUser->id,
            'original_user_email' => $originalUser->email,
            'target_user_id' => $targetUser->id,
            'target_user_email' => $targetUser->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}