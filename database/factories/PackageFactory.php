<?php

namespace Database\Factories;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Showroom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'showrooms_id' => Showroom::factory(),
            'name' => fake()->words(3, true),
            'sku' => 'PKG-'.fake()->unique()->numerify('####'),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'price' => fake()->randomFloat(2, 20000, 100000),
            'status' => PackageStatus::DRAFT,
            'current_step' => 1,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PackageStatus::ARCHIVED,
        ]);
    }
}
