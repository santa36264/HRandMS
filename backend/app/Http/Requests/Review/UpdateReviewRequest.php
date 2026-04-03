<?php

namespace App\Http\Requests\Review;

use App\Traits\ValidatesJson;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    use ValidatesJson;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'rating'             => ['sometimes', 'integer', 'min:1', 'max:5'],
            'cleanliness_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'service_rating'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'location_rating'    => ['nullable', 'integer', 'min:1', 'max:5'],
            'title'              => ['nullable', 'string', 'max:120'],
            'comment'            => ['nullable', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer'             => 'Rating must be a whole number.',
            'rating.min'                 => 'Rating must be at least 1 star.',
            'rating.max'                 => 'Rating may not exceed 5 stars.',
            'cleanliness_rating.integer' => 'Cleanliness rating must be a whole number.',
            'cleanliness_rating.min'     => 'Cleanliness rating must be at least 1.',
            'cleanliness_rating.max'     => 'Cleanliness rating may not exceed 5.',
            'service_rating.integer'     => 'Service rating must be a whole number.',
            'service_rating.min'         => 'Service rating must be at least 1.',
            'service_rating.max'         => 'Service rating may not exceed 5.',
            'location_rating.integer'    => 'Location rating must be a whole number.',
            'location_rating.min'        => 'Location rating must be at least 1.',
            'location_rating.max'        => 'Location rating may not exceed 5.',
            'title.max'                  => 'Review title may not exceed 120 characters.',
            'comment.min'                => 'Review comment must be at least 10 characters.',
            'comment.max'                => 'Review comment may not exceed 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rating'             => 'overall rating',
            'cleanliness_rating' => 'cleanliness rating',
            'service_rating'     => 'service rating',
            'location_rating'    => 'location rating',
            'title'              => 'review title',
            'comment'            => 'review comment',
        ];
    }
}
