<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer'],
            'icon' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
