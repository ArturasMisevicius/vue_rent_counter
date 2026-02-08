<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    protected $model = Faq::class;

    public function definition(): array
    {
        return [
            'question' => $this->faker->sentence(8),
            'answer' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['Billing', 'Meters', 'Access', 'Support']),
            'display_order' => $this->faker->numberBetween(0, 50),
            'is_published' => true,
        ];
    }
}
