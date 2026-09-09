<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['pdf', 'docx', 'txt', 'md', 'png', 'jpg', 'jpeg'])->max('20mb')],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer'],
            'tag_ids' => ['sometimes', 'array', 'max:20'],
            'tag_ids.*' => ['integer', 'distinct'],
            'is_sensitive' => ['sometimes', 'boolean'],
        ];
    }
}
