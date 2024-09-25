<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class UpdateCredentialsRequest extends FormRequest
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
        return [
            'display_name' => 'string',
            'note' => 'string',
            'avatar' => 'image',
            'header' => 'image',
            'locked' => 'string',
            'bot' => 'nullable',
            'discoverable' => 'nullable',
            'hide_collections' => 'nullable',
            'indexable' => 'nullable',
            'fields_attributes.*.name' => 'nullable|max:255',
            'fields_attributes.*.value' => 'nullable|max:255',
            'source.*.privacy' => 'nullable',
            'source.*.sensitive' => 'nullable',
            'source.*.language' => 'nullable',
        ];
    }
}
