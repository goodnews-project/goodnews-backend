<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class UserRequest extends FormRequest
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
            'username'      => 'required|unique:account|regex:/^[a-zA-Z0-9 ]+$/|string|between:3,20',
            'display_name'  => 'required|unique:account|string|between:3,20',
            'email'         => 'required|email|unique:user', //
            'password'      => 'required|string|between:6,18',
            'client_id'     => 'nullable',
            'response_type' => 'nullable',
            'redirect_uri'  => 'nullable',
            'locale'        => 'nullable',
        ];
    }
}
