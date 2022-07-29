<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThematicSelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'summary_compilations' => json_decode($this->get('summary_compilations'), true),
            'is_publish' => $this->get('is_publish', 0) === 'on' ? 1 : 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $method = $this->getMethod();

        if ($method === 'POST') {
            $rules = [
                'title' => 'required',
                'sort_order' => 'required',
                'image' => 'nullable|file',
                'audio_name' => 'nullable|file',
            ];
        } else {
            $rules = [
                'title' => 'nullable',
                'sort_order' => 'nullable',
                'image' => 'nullable|file',
                'audio_name' => 'nullable|file',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Необходимо указать Название',
            'sort_order.required' => 'Необходимо указать Порядок сортировки',
        ];
    }
}
