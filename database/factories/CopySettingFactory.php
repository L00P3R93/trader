<?php

namespace Database\Factories;

use App\Models\CopySetting;
use App\Models\DerivConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CopySetting>
 */
class CopySettingFactory extends Factory
{
    protected $model = CopySetting::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'master_connection_id' => DerivConnection::factory()->state(['type' => 'master']),
            'min_consecutive_wins' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }

    public function paused(): static
    {
        return $this->state(['is_active' => false]);
    }
}
