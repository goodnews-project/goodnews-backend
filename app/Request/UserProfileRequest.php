<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class UserProfileRequest extends FormRequest
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
            'avatar_attachment_id'        => 'exists:attachment,id',
            'profile_image_attachment_id' => 'exists:attachment,id',
            'display_name'                => 'string|between:3,15',
            'note'                        => 'max:255',
            'manually_approves_follower'  => 'in:0,1',
            'enable_activitypub'  => 'in:0,1',
            'is_sensitive'  => 'in:0,1',
            'is_display_sensitive'  => 'in:0,1',
            'is_bot'  => 'bool',
            'fields' => 'array',
            'fields.*.name' => 'string',
            'fields.*.type' => 'in:PropertyValue',
            'fields.*.value' => 'string',
            'fee' => 'nullable',
            'is_long_term' => 'in:0,1',
            'wallet_address' => 'string'
        ];
    }
}
