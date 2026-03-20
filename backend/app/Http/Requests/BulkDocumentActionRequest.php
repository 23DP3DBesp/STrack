<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDocumentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:archive,unarchive,trash,restore,purge,star,unstar,move'],
            'document_ids' => ['required', 'array', 'min:1', 'max:200'],
            'document_ids.*' => ['integer', 'distinct'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
        ];
    }
}
