<?php

declare(strict_types=1);

namespace App\Request\Admin\Settings;

use Hyperf\Validation\Request\FormRequest;

class AboutRequest extends FormRequest
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
            'site_extended_description'    => 'nullable',
            'show_domain_blocks'           => 'nullable',
            'show_domain_blocks_rationale' => 'nullable',
            'status_page_url'              => 'nullable|url',
            'site_terms'                   => 'nullable',
        ];
    }
}
