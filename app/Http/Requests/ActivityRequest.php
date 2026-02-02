<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'duration' => 'required|string|max:100',
            'difficulty' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'min_age' => 'nullable|integer|min:0',
            'max_participants' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'inclusions' => 'nullable|string',
            'requirements' => 'nullable|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'gallery_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'is_featured' => 'required|boolean',
            'is_active' => 'required|boolean',
            'season' => 'nullable|string|max:100',
        ];

        // For update requests, make featured_image optional
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['featured_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240';
        }

        return $rules;
    }
}
