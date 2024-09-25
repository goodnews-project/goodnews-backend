<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

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
            'q' => 'nullable|string',
            'type' => 'nullable|string',
            'resolve' => 'nullable',
            'following' => 'nullable',
            'account_id' => 'nullable|string',
            'exclude_unreviewed' => 'nullable',
            'max_id' => 'nullable|string',
            'min_id' => 'nullable|string',
            'limit' => 'nullable|int',
            'offset' => 'nullable|int',
        ];
    }
}
