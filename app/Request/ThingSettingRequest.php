<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class ThingSettingRequest extends FormRequest
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
            'display_media' => 'nullable|in:1,2,3',
            'default_privacy' => 'nullable|in:1,2,4',
            'default_sensitive' => 'nullable|in:0,1',
            'show_application' => 'nullable|in:0,1',
            'use_blurhash' => 'nullable|in:0,1',
            'expand_spoilers' => 'nullable|in:0,1',
            'publish_language' => 'nullable|string',
            'filter_language' => 'nullable|array',
        ];
    }
}
