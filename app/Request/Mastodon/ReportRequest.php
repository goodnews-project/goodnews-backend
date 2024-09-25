<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class ReportRequest extends FormRequest
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
            'account_id' => 'required|string',
            'status_ids' => 'array',
            'comment' => 'string',
            'forward' => 'nullable',
            'forward_to_domains' => 'array',
            'category' => 'string',
            'rule_ids' => 'array',
        ];
    }
}
