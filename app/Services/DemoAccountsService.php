<?php

namespace App\Services;

use Database\Seeders\DemoAccountsSeeder;

/**
 * Service for handling demo accounts functionality.
 */
class DemoAccountsService
{
    /**
     * Check if demo accounts are enabled.
     */
    public function isEnabled(): bool
    {
        return config('app.demo_accounts_enabled', false);
    }

    /**
     * Get demo accounts data for frontend usage.
     * Returns empty array if demo accounts are disabled.
     */
    public function getDemoAccounts(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        return DemoAccountsSeeder::getDemoAccountsForFrontend();
    }

    /**
     * Get demo accounts formatted for dropdown/selection.
     */
    public function getDemoAccountsForDropdown(): array
    {
        $accounts = $this->getDemoAccounts();

        return array_map(function ($account) {
            return [
                'value' => $account['email'],
                'label' => $account['description'] . ' (' . $account['email'] . ')',
                'email' => $account['email'],
                'password' => $account['password'],
                'role' => $account['role'],
                'name' => $account['name'],
            ];
        }, $accounts);
    }

    /**
     * Get demo account by email.
     */
    public function getDemoAccountByEmail(string $email): ?array
    {
        $accounts = $this->getDemoAccounts();

        foreach ($accounts as $account) {
            if ($account['email'] === $email) {
                return $account;
            }
        }

        return null;
    }

    /**
     * Check if an email belongs to a demo account.
     */
    public function isDemoAccountEmail(string $email): bool
    {
        return $this->getDemoAccountByEmail($email) !== null;
    }

    /**
     * Get demo accounts grouped by role.
     */
    public function getDemoAccountsByRole(): array
    {
        $accounts = $this->getDemoAccounts();
        $grouped = [];

        foreach ($accounts as $account) {
            $role = $account['role'];
            if (!isset($grouped[$role])) {
                $grouped[$role] = [];
            }
            $grouped[$role][] = $account;
        }

        return $grouped;
    }
}
