<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class AuthorizeRequest extends FormRequest
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
            'response_type' => 'required|string',
            'client_id' => 'required|string',
            'redirect_uri' => 'required|string',
            'scope' => 'nullable|string',
            'force_login' => 'nullable',
            'lang' => 'nullable|string',
        ];
    }
}
