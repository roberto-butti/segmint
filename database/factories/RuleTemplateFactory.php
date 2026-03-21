<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\RuleTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuleTemplate>
 */
class RuleTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(3, true),
            'type' => 'comparison',
            'key' => 'utms.utm_source',
            'operator' => '=',
            'value' => fake()->word(),
        ];
    }
}
