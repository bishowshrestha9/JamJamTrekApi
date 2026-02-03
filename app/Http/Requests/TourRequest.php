<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TourRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'description' => 'nullable|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'discount_price' => 'nullable|numeric|min:0',

            'duration_days' => 'required|integer|min:1',
            'duration_nights' => 'required|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            'difficulty_level' => 'required|string|in:Easy,Moderate,Challenging,Extreme',
            'max_group_size' => 'required|integer|min:1',
            'min_group_size' => 'required|integer|min:1',
            'tour_type' => 'required|string|max:100',

            'inclusions' => 'nullable|array',
            'inclusions.*' => 'string',
            'exclusions' => 'nullable|array',
            'exclusions.*' => 'string',

            'accommodation_details' => 'nullable|array',
            'meal_plan' => 'nullable|array',
            'itinerary' => 'nullable|array',

            'guide_included' => 'boolean',
            'guide_language' => 'nullable|string|max:100',
            'porter_included' => 'boolean',

            'requirements' => 'nullable|string',
            'what_to_bring' => 'nullable|string',

            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_popular' => 'boolean',

            'available_slots' => 'required|integer|min:0',
            'instant_booking' => 'boolean',

            'slug' => 'nullable|string|unique:tours,slug,' . $this->route('tour'),
            'meta_description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Tour title is required',
            'destination.required' => 'Destination is required',
            'price.required' => 'Price is required',
            'duration_days.required' => 'Duration in days is required',
            'difficulty_level.in' => 'Difficulty level must be Easy, Moderate, Challenging, or Extreme',
        ];
    }
}
