<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class TokenRequest extends FormRequest
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
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
            'redirect_uri' => 'required|string',
            'grant_type' => 'required|string',
            'code' => 'nullable|string',
            'scope' => 'nullable|string',
        ];
    }
}
