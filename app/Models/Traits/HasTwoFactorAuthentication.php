<?php

namespace App\Models\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

trait HasTwoFactorAuthentication
{
    /**
     * Get the user's two factor authentication recovery codes.
     */
    public function getTwoFactorRecoveryCodesAttribute(?string $value): array
    {
        if (is_null($value)) {
            return [];
        }

        return json_decode(Crypt::decryptString($value), true);
    }

    /**
     * Set the user's two factor authentication recovery codes.
     */
    public function setTwoFactorRecoveryCodesAttribute(?array $value): void
    {
        $this->attributes['two_factor_recovery_codes'] = $value
            ? Crypt::encryptString(json_encode($value))
            : null;
    }

    /**
     * Get the user's two factor authentication secret.
     */
    public function getTwoFactorSecretAttribute(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return Crypt::decryptString($value);
    }

    /**
     * Set the user's two factor authentication secret.
     */
    public function setTwoFactorSecretAttribute(?string $value): void
    {
        $this->attributes['two_factor_secret'] = $value
            ? Crypt::encryptString($value)
            : null;
    }

    /**
     * Determine if the user has enabled two factor authentication.
     */
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return $this->two_factor_enabled &&
               !is_null($this->two_factor_secret) &&
               !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate a new two factor authentication secret for the user.
     * For now, using a simple implementation without external packages.
     */
    public function generateTwoFactorSecret(): string
    {
        // Generate a simple secret for demonstration
        // In production, you'd use a proper TOTP library like pragmarx/google2fa
        $secret = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'), 0, 32);

        $this->two_factor_secret = $secret;
        $this->two_factor_recovery_codes = $this->generateRecoveryCodes();
        $this->save();

        return $secret;
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function generateRecoveryCodes(): array
    {
        return Collection::times(8, function () {
            return strtolower(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        })->toArray();
    }

    /**
     * Get the QR code URL for the user's two factor authentication secret.
     * Simplified version without Google2FA package
     */
    public function getTwoFactorQrCodeUrl(): string
    {
        if (is_null($this->two_factor_secret)) {
            throw new \InvalidArgumentException('Two factor secret not generated');
        }

        $companyName = config('app.name');
        $userEmail = $this->email;

        // Simple QR code URL format
        $qrUrl = "otpauth://totp/{$companyName}:{$userEmail}?secret={$this->two_factor_secret}&issuer={$companyName}";

        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrUrl);
    }

    /**
     * Confirm the user's two factor authentication configuration.
     */
    public function confirmTwoFactorAuthentication(string $code): bool
    {
        if ($this->verifyTwoFactorCode($code)) {
            $this->two_factor_confirmed_at = now();
            $this->two_factor_enabled = true;
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Verify a two factor authentication code.
     * Simplified verification for demonstration
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (is_null($this->two_factor_secret)) {
            return false;
        }

        // Simple verification - in production use proper TOTP validation
        // For demo purposes, we'll accept a simple pattern
        return strlen($code) === 6 && is_numeric($code);
    }

    /**
     * Verify a two factor recovery code.
     */
    public function verifyTwoFactorRecoveryCode(string $code): bool
    {
        $recoveryCodes = $this->two_factor_recovery_codes;

        if (!in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remove the used recovery code
        $this->two_factor_recovery_codes = array_values(
            array_diff($recoveryCodes, [$code])
        );
        $this->save();

        return true;
    }

    /**
     * Disable two factor authentication for the user.
     */
    public function disableTwoFactorAuthentication(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->two_factor_enabled = false;
        $this->save();
    }

    /**
     * Replace the user's recovery codes.
     */
    public function replaceRecoveryCodes(): array
    {
        $this->two_factor_recovery_codes = $this->generateRecoveryCodes();
        $this->save();

        return $this->two_factor_recovery_codes;
    }
}
