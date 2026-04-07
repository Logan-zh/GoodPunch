<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(['annual', 'personal', 'sick']),
            'start_at' => now()->addDays(1)->setTime(9, 0),
            'end_at' => now()->addDays(1)->setTime(18, 0),
            'hours' => 8,
            'reason' => $this->faker->sentence(),
            'status' => 'pending',
            'current_step' => 0,
        ];
    }
}
