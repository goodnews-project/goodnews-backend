<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class StatuesRequest extends FormRequest
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
            'content'       => 'required|string|max:500',
            'scope'         => 'in:1,2,3,4',
            'who_can_reply' => 'nullable',
            'reply_to_id'   => 'nullable',
            'is_sensitive'  => 'nullable',
            'attachments'   => 'array',
            'poll'          => 'array',
            'enable_activitypub' => 'in:0,1',
            'fee' => 'nullable'
        ];
    }
}
