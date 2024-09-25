<?php

declare(strict_types=1);

namespace App\Request\Admin\Settings;

use Hyperf\Validation\Request\FormRequest;

class BrandingRequest extends FormRequest
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
            'site_title'             => 'nullable',
            'site_contact_username'  => 'nullable',
            'site_contact_email'     => 'nullable',
            'site_short_description' => 'nullable',
            'thumbnail_id'           => 'nullable',
            'receive_remote_sensitive'     => 'nullable',
            'push_local_sensitive'         => 'nullable',
        ];
    }
}
