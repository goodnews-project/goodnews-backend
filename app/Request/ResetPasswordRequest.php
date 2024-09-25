<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class ResetPasswordRequest extends FormRequest
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
        if($this->is('*mail')){
            return [
                'email' => 'required|email|exists:user'
            ];
        }
        return [
            'token'=>'required',
            'password'=> 'required|string|between:6,18'
        ];
        
    }
}
