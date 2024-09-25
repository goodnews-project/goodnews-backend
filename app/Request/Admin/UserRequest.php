<?php

declare(strict_types=1);

namespace App\Request\Admin;

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
            'position' => 'in:0,1,2|nullable',
            'status' => 'nullable',
            'role_id' => 'nullable',
            'sortord' => 'nullable',
            'username' => 'nullable',
            'domain' => 'nullable',
            'nickname' => 'nullable',
            'email' => 'nullable|email',
            'ip' => 'nullable|ip',
            'is_match_all' => 'nullable',
        ];
    }
}
