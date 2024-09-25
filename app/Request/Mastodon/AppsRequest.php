<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class AppsRequest extends FormRequest
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
            'client_name' => 'required|string',
            'redirect_uris' => 'required|string',
            'scopes' => 'nullable|string',
            'website' => 'nullable|string',
        ];
    }
}
