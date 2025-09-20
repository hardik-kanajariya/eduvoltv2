<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Service for automatically registering policies
 *
 * Discovers and registers all policies in the app/Policies directory,
 * following Laravel naming conventions to map policies to models.
 */
class PolicyRegistrationService
{
    /**
     * Automatically register all policies found in the Policies directory.
     */
    public function registerPolicies(): void
    {
        $policies = $this->discoverPolicies();

        foreach ($policies as $modelClass => $policyClass) {
            Gate::policy($modelClass, $policyClass);
        }
    }

    /**
     * Discover all policy classes and their corresponding models.
     */
    protected function discoverPolicies(): array
    {
        $policies = [];
        $policiesPath = app_path('Policies');

        if (!is_dir($policiesPath)) {
            return $policies;
        }

        $finder = new Finder();
        $finder->files()->in($policiesPath)->name('*.php');

        foreach ($finder as $file) {
            $policyClass = $this->getClassFromFile($file->getPathname());

            if (!$policyClass || !class_exists($policyClass)) {
                continue;
            }

            // Skip base classes and interfaces
            if ($this->shouldSkipClass($policyClass)) {
                continue;
            }

            $modelClass = $this->getModelClassFromPolicy($policyClass);

            if ($modelClass && class_exists($modelClass)) {
                $policies[$modelClass] = $policyClass;
            }
        }

        return $policies;
    }

    /**
     * Get the fully qualified class name from a file path.
     */
    protected function getClassFromFile(string $filePath): ?string
    {
        $relativePath = str_replace(app_path() . '/', '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);
        $className = str_replace('/', '\\', $relativePath);

        return 'App\\' . $className;
    }

    /**
     * Determine if a class should be skipped during policy registration.
     */
    protected function shouldSkipClass(string $class): bool
    {
        $reflection = new ReflectionClass($class);

        // Skip abstract classes
        if ($reflection->isAbstract()) {
            return true;
        }

        // Skip interfaces
        if ($reflection->isInterface()) {
            return true;
        }

        // Skip base policy classes
        if (Str::contains($class, ['BaseTenantPolicy', 'BasePolicy'])) {
            return true;
        }

        // Skip classes in Contracts namespace
        if (Str::contains($class, '\\Contracts\\')) {
            return true;
        }

        return false;
    }

    /**
     * Get the model class name from a policy class name.
     *
     * Follows Laravel convention: UserPolicy -> User model
     */
    protected function getModelClassFromPolicy(string $policyClass): ?string
    {
        // Extract class name without namespace
        $className = class_basename($policyClass);

        // Remove 'Policy' suffix
        if (!Str::endsWith($className, 'Policy')) {
            return null;
        }

        $modelName = Str::replaceLast('Policy', '', $className);

        // Try common model locations
        $possibleModelClasses = [
            "App\\Models\\{$modelName}",
            "App\\{$modelName}",
        ];

        foreach ($possibleModelClasses as $modelClass) {
            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return null;
    }

    /**
     * Register a specific policy manually.
     */
    public function registerPolicy(string $modelClass, string $policyClass): void
    {
        if (!class_exists($modelClass) || !class_exists($policyClass)) {
            throw new \InvalidArgumentException("Model or Policy class does not exist.");
        }

        Gate::policy($modelClass, $policyClass);
    }

    /**
     * Get all registered policies.
     */
    public function getRegisteredPolicies(): array
    {
        return Gate::policies();
    }

    /**
     * Check if a model has a registered policy.
     */
    public function hasPolicy(string $modelClass): bool
    {
        return array_key_exists($modelClass, Gate::policies());
    }
}
