<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrekRequest extends FormRequest
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
        
        return [
            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|string|max:100',
            'difficulty' => 'required|string|max:100',
            'type' => 'required|string|max:100',
            'distance_km' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'featured_image' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'is_featured' => 'required|boolean',
            'is_active' => 'required|boolean',
            'currency' => 'required|string|max:100',
            'trek_days' => 'required|array|min:1',
            'trek_days.*' => 'required|string|max:1000',
            'data_type' => 'required|in:trek,package',
        ];
    }

    protected function prepareForValidation()
    {
        if (is_string($this->trek_days)) {
            $this->merge([
                'trek_days' => array_map('trim', explode(',', $this->trek_days)),
            ]);
        }
        if (is_string($this->is_featured)) {
            $isBoolean = filter_var($this->is_featured, FILTER_VALIDATE_BOOLEAN);
            $this->merge([
                'is_featured' => $isBoolean ?  $isBoolean : ($this->is_featured == 'true' ? true : false)
            ]);
        }
        if (is_string($this->is_active)) {
            $isBoolean = filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN);
            $this->merge([
                'is_active' => $isBoolean ?  $isBoolean : ($this->is_active == 'true' ? true : false)
            ]);
        }
    }
}