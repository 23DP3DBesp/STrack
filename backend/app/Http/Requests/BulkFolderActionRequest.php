<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkFolderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:delete,duplicate'],
            'folder_ids' => ['required', 'array', 'min:1', 'max:200'],
            'folder_ids.*' => ['integer', 'distinct'],
        ];
    }
}

