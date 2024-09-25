<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Admin\IpBlock;
use Hyperf\Validation\Request\FormRequest;

class IpBlockRequest extends FormRequest
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
            'ip' => 'required',
            'expires_in' => 'required|in:'.join(',', array_keys(IpBlock::EXPIRES_IN_MAP)),
            'severity' => 'required|in:1,2,3',
            'comment' => 'nullable',
        ];
    }
}
