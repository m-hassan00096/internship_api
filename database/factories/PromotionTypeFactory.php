<?php

namespace Database\Factories;

use App\Models\PromotionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionType>
 */
class PromotionTypeFactory extends Factory
{
    protected $model = PromotionType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'ar' => fake()->randomElement(['بانر رئيسي مميز', 'ترويج في الصفحة الرئيسية', 'أعلى نتائج البحث']),
                'en' => fake()->randomElement(['Featured Main Banner', 'Home Page Promotion', 'Top Search Results']),
            ],
            'placement' => fake()->randomElement(['banner', 'home', 'search', 'category']),
            'daily_price' => fake()->randomFloat(2, 50, 500),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
