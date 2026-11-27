<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

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
            'image_path' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
