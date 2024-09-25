<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class PayLogRequest extends FormRequest
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
            'type' => 'required|in:1,2,3',
            'reward_type' => 'in:1,2',
            'amount' => 'min:1',
            'target_account_id'=>'nullable|exists:account,id',
            'status_id' => 'nullable|exists:status,id',
            'plan_id' => 'nullable',
        ];
    }
}
