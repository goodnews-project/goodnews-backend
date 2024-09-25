<?php

declare(strict_types=1);

namespace App\Request\Admin;

use Hyperf\Validation\Request\FormRequest;

class InstanceSettingRequest extends FormRequest
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
            'is_disable_download'=> 'filled|in:0,1',
            'is_proxy'=>'filled|in:0,1',
            'is_disable_sync'=>'filled|in:0,1',
        ];
    }
}
