<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\HttpStatusCode;
use App\Helpers\ApiResponse;
use App\Models\PromotionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePromotionPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $prices = $this->input('prices');

        // Allow map of placement prices, e.g.: { room_price: 200, package_price: 250, banner_price: 500 }
        if (empty($prices)) {
            $mapped = [];
            $allTypes = PromotionType::all()->keyBy('placement');

            $placementMappings = [
                'room_price' => ['room', 'home'],
                'roomPrice' => ['room', 'home'],
                'package_price' => ['package'],
                'packagePrice' => ['package'],
                'banner_price' => ['banner'],
                'bannerPrice' => ['banner'],
            ];

            foreach ($placementMappings as $key => $placements) {
                if ($this->has($key)) {
                    foreach ($placements as $pl) {
                        if (isset($allTypes[$pl])) {
                            $mapped[] = [
                                'id' => $allTypes[$pl]->id,
                                'daily_price' => (float) $this->input($key),
                            ];
                        }
                    }
                }
            }

            if (! empty($mapped)) {
                $prices = $mapped;
            }
        }

        // Normalize camelCase dailyPrice and parse any string currency
        if (is_array($prices)) {
            foreach ($prices as $i => $item) {
                if (is_array($item)) {
                    $raw = $item['dailyPrice'] ?? $item['daily_price'] ?? null;
                    if ($raw !== null) {
                        $clean = is_string($raw) ? preg_replace('/[^0-9.]/', '', $raw) : $raw;
                        $prices[$i]['daily_price'] = (float) $clean;
                    }
                }
            }
        }

        $this->merge([
            'prices' => $prices,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.id' => ['required', 'integer', 'exists:promotion_types,id'],
            'prices.*.daily_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'prices' => __('admin/promotion.attributes.prices'),
            'prices.*.daily_price' => __('admin/promotion.attributes.daily_price'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prices.required' => __('admin/promotion.errors.prices_required'),
            'prices.array' => __('admin/promotion.errors.prices_array'),
            'prices.*.daily_price.required' => __('admin/promotion.errors.price_invalid'),
            'prices.*.daily_price.numeric' => __('admin/promotion.errors.price_invalid'),
            'prices.*.daily_price.min' => __('admin/promotion.errors.price_invalid'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(
                $validator->errors()->first(),
                HttpStatusCode::VALIDATION_ERROR
            )
        );
    }
}
