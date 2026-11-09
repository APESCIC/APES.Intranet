<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teams = ['Care', 'Education', 'Operations', 'Fundraising', 'Office'];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->userName().'@example.test',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
            'job_title' => fake()->optional()->jobTitle(),
            'team' => fake()->randomElement($teams),
            // Ofcom's drama ranges, so no fake number can belong to anyone.
            'phone' => fake()->optional()->numerify('0113 496 0###'),
            'mobile' => fake()->optional()->numerify('07700 900###'),
            'location' => fake()->optional()->city(),
            'skills' => fake()->randomElements(['First aid', 'Driving', 'Admin', 'Animal care', 'Fundraising', 'IT'], fake()->numberBetween(0, 3)),
            'bio' => fake()->optional()->sentence(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('07700 900###'),
            'employee_number' => 'E'.fake()->unique()->numerify('####'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => now(),
        ]);
    }
}
