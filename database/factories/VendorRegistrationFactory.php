<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\BusinessType;
use App\Models\VendorRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorRegistration>
 */
class VendorRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_number' => 'REG-'.date('Y').'-'.fake()->unique()->numerify('####'),
            'vendor_name' => 'معرض '.fake()->company(),
            'business_type_id' => BusinessType::factory(),
            'contact_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('010########'),
            'has_whatsapp' => true,
            'status' => RegistrationStatus::NEW,
            'user_id' => null,
        ];
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::UNDER_REVIEW,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::APPROVED,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RegistrationStatus::REJECTED,
        ]);
    }
}
