<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class RegRequest extends FormRequest
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
            'username' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string',
            'agreement' => 'required',
            'locale' => 'required|string',
            'reason' => 'string',
        ];
    }
}
