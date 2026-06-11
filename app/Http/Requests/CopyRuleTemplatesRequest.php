<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyRuleTemplatesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('project'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $sourceProject */
        $sourceProject = $this->route('project');

        return [
            'destination_project_id' => [
                'required',
                'integer',
                Rule::exists('projects', 'id')->whereNot('id', $sourceProject->id),
            ],
            'rule_template_ids' => ['required', 'array', 'min:1'],
            'rule_template_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('rule_templates', 'id')->where('project_id', $sourceProject->id),
            ],
        ];
    }
}
