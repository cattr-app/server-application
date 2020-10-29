<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Http\Requests\FormRequest;

class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'description' => 'required|string',
            'important' => 'sometimes|required|bool'
        ];
    }
}
