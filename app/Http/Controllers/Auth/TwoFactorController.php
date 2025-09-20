<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    /**
     * Show the two factor authentication setup page.
     */
    public function show(): View
    {
        $user = auth()->user();

        return view('auth.two-factor', [
            'user' => $user,
            'qrCodeUrl' => $user->hasEnabledTwoFactorAuthentication()
                ? null
                : $user->getTwoFactorQrCodeUrl(),
            'recoveryCodes' => $user->hasEnabledTwoFactorAuthentication()
                ? $user->two_factor_recovery_codes
                : [],
        ]);
    }

    /**
     * Enable two factor authentication for the user.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $this->respondWithError('Two factor authentication is already enabled.');
        }

        // Generate secret if not exists
        if (is_null($user->two_factor_secret)) {
            $user->generateTwoFactorSecret();
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if (!$user->confirmTwoFactorAuthentication($request->code)) {
            return $this->respondWithError('The provided two factor authentication code was invalid.');
        }

        return $this->respondWithSuccess('Two factor authentication has been enabled.');
    }

    /**
     * Disable two factor authentication for the user.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user->disableTwoFactorAuthentication();

        return $this->respondWithSuccess('Two factor authentication has been disabled.');
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function recoveryCodes(): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        if (!$user->hasEnabledTwoFactorAuthentication()) {
            return $this->respondWithError('Two factor authentication is not enabled.');
        }

        $recoveryCodes = $user->replaceRecoveryCodes();

        if (request()->expectsJson()) {
            return response()->json([
                'recovery_codes' => $recoveryCodes,
            ]);
        }

        return back()->with([
            'status' => 'New recovery codes have been generated.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Show the two factor challenge form.
     */
    public function challenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the two factor authentication challenge.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required_without:recovery_code', 'string'],
            'recovery_code' => ['required_without:code', 'string'],
        ]);

        $user = auth()->user();

        if (!$user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Two factor authentication is not properly configured.',
            ]);
        }

        $verified = false;

        if ($request->filled('code')) {
            $verified = $user->verifyTwoFactorCode($request->code);
        } elseif ($request->filled('recovery_code')) {
            $verified = $user->verifyTwoFactorRecoveryCode($request->recovery_code);
        }

        if (!$verified) {
            return back()->withErrors([
                'code' => 'The provided two factor authentication code was invalid.',
            ]);
        }

        // Mark the session as verified
        session(['two_factor_verified' => true]);

        return redirect()->intended('/dashboard');
    }

    /**
     * Generate a QR code for the user.
     */
    public function qrCode(): JsonResponse
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return response()->json(['error' => 'Two factor authentication is already enabled.'], 400);
        }

        if (is_null($user->two_factor_secret)) {
            $user->generateTwoFactorSecret();
        }

        return response()->json([
            'qr_code_url' => $user->getTwoFactorQrCodeUrl(),
            'secret' => $user->two_factor_secret,
            'recovery_codes' => $user->two_factor_recovery_codes,
        ]);
    }

    /**
     * Respond with success message.
     */
    private function respondWithSuccess(string $message): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Respond with error message.
     */
    private function respondWithError(string $message): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['error' => $message], 422);
        }

        return back()->withErrors(['code' => $message]);
    }
}
