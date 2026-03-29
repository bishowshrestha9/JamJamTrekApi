<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert string boolean values to actual booleans
        if ($this->has('is_active')) {
            $value = $this->input('is_active');
            
            // Convert string "true"/"false" or "1"/"0" to boolean
            if (is_string($value)) {
                $this->merge([
                    'is_active' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Check if this is an update request (has id in route)
        $isUpdate = $this->route('id') !== null;
        
        $rules = [
            'caption' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];

        // For create, image is required. For update, it's optional
        if ($isUpdate) {
            $rules['image'] = 'nullable|file|image|mimes:jpeg,jpg,png,gif,webp|max:5120';
        } else {
            $rules['image'] = 'required|file|image|mimes:jpeg,jpg,png,gif,webp|max:10240'; // 10MB limit for gallery images
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Image is required',
            'image.file' => 'The image must be a file',
            'image.image' => 'The uploaded file must be an image',
            'image.mimes' => 'The image must be a file of type: jpeg, jpg, png, gif, webp',
            'image.max' => 'The image may not be greater than 10MB',
        ];
    }
}
