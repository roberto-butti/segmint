<?php

namespace App\Http\Requests;

use App\Services\SegmentRules\SegmentRuleOperator;
use App\Services\SegmentRules\SegmentRuleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSegmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('project')->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['required', 'boolean'],
            'rules' => ['nullable', 'array'],
            'rules.*.type' => ['required', Rule::enum(SegmentRuleType::class)],
            'rules.*.key' => ['required', 'string', 'max:255'],
            'rules.*.operator' => ['required', Rule::enum(SegmentRuleOperator::class)],
            'rules.*.value' => ['required', 'string', 'max:1000'],
            'rules.*.priority' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
