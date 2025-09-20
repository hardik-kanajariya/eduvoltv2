<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $companyName = fake()->company();
        $domain = strtolower(str_replace([' ', '.', ','], '', $companyName)) . '.eduvolt.test';

        return [
            'name' => $companyName,
            'domain' => $domain,
            'database' => null,
            'settings' => [
                'timezone' => fake()->timezone(),
                'locale' => 'en',
                'theme' => fake()->randomElement(['light', 'dark']),
            ],
            'is_active' => fake()->boolean(90), // 90% chance of being active
        ];
    }
}
