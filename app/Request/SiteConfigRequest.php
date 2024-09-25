<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class SiteConfigRequest extends FormRequest
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
            'configs'         => 'array',
            'configs.*.key'   => 'required|string',
            'configs.*.type'  => 'required|string|in:text,json',
            'configs.*.value' => 'required|string',
        ];
    }
}
