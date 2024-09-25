<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class PaginateRequest extends FormRequest
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
            'max_id' => 'nullable|string',
            'since_id' => 'nullable|string',
            'min_id' => 'nullable|string',
            'limit' => 'nullable|integer|between:1,200',
        ];
    }
}
