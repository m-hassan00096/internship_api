<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $nameAr = fake()->words(2, true);
        $nameEn = fake()->words(2, true);

        return [
            'name' => [
                'ar' => $nameAr,
                'en' => $nameEn,
            ],
            'slug' => Str::slug($nameEn).'-'.Str::random(5),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
