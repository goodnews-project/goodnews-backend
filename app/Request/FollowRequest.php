<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\Rule;

class FollowRequest extends FormRequest
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
            '@context' => 'required',
            'id' => 'required|string',
            'type' => [
                'required',
                Rule::in(['Follow'])
            ],
            'actor' => 'required|url',
            'object' => 'required|url'
        ];
    }
}
