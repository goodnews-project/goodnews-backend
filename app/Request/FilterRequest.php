<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class FilterRequest extends FormRequest
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
            'id' => 'nullable',
            'title' => 'required|string',
            'expires_in' => 'int',
            'context' => 'required|array',
            'act' => 'required',
            'kw_attr' => 'array',
        ];
    }
}
