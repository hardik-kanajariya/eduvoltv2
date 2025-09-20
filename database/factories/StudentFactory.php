<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'admission_number' => 'STU' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'date_of_birth' => $this->faker->dateTimeBetween('-18 years', '-5 years'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'address' => $this->faker->address(),
            'enrollment_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'student_id' => $this->faker->unique()->bothify('STD###??'),
            'grade' => $this->faker->randomElement(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12']),
            'section' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'parent_name' => $this->faker->name(),
            'parent_phone' => $this->faker->phoneNumber(),
            'parent_email' => $this->faker->safeEmail(),
            'parent_relationship' => $this->faker->randomElement(['father', 'mother', 'guardian']),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->randomElement(['uncle', 'aunt', 'grandparent', 'family_friend']),
            'medical_conditions' => $this->faker->optional(0.3)->passthrough(['asthma', 'diabetes']),
            'allergies' => $this->faker->optional(0.2)->passthrough(['peanuts', 'shellfish', 'dairy']),
            'medications' => $this->faker->optional(0.2)->passthrough(['inhaler', 'insulin']),
            'emergency_medical_info' => $this->faker->optional()->sentence(),
            'previous_school' => $this->faker->optional()->company() . ' School',
            'admission_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'academic_year' => $this->faker->randomElement(['2023-24', '2024-25', '2025-26']),
        ];
    }
}
