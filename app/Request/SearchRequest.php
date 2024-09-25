<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class SearchRequest extends FormRequest
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
            'q' => 'required|string|min:1|max:100',
            'type' => 'nullable|in:accounts,statuses,hashtags',
            'resolve' => 'nullable',
            'limit' => 'nullable|integer|max:40',
            'offset' => 'nullable|integer',
            'following' => 'nullable'
        ];
    }
}
