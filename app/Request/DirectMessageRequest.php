<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Attachment;
use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\Rule;

class DirectMessageRequest extends FormRequest
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
            'to_id' => 'required',
            'message' => 'required|string|min:1|max:500',
            'type'  => 'required|in:1,2,3',
            'url'  => 'url',
            'file_type'  => 'in:1,2,3,4'
        ];
    }
}
