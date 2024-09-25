<?php

declare(strict_types=1);

namespace App\Request\Mastodon;

use Hyperf\Validation\Request\FormRequest;

class StatusRequest extends FormRequest
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
            'status' => 'string',
            'media_ids' => 'array',
            'poll' => 'array',
            'poll.options' => 'array',
            'poll.expires_in' => 'integer',
            'poll.multiple' => 'nullable',
            'poll.hide_totals' => 'nullable',
            'in_reply_to_id' => 'string',
            'sensitive' => 'nullable',
            'spoiler_text' => 'string',
            'visibility' => 'string|in:public,unlisted,private,direct',
            'language' => 'string',
            'scheduled_at' => 'string',
        ];
    }
}
