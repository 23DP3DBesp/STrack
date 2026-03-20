<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFileKb = max(1, (int) config('docbox.upload.max_file_kb', 102400));

        return [
            'title' => ['sometimes', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'file' => ['nullable', 'file', 'max:'.$maxFileKb],
            'is_archived' => ['nullable', 'boolean'],
            'is_starred' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (!$this->hasFile('file') || !$this->user()) {
                return;
            }
            if (in_array($this->user()->role, ['admin', 'manager'], true)) {
                return;
            }

            $document = $this->route('document');
            $currentDocumentSize = $document instanceof Document ? (int) $document->size : 0;
            $quotaMb = max(1, (int) config('docbox.upload.free_plan_quota_mb', 512));
            $quotaBytes = $quotaMb * 1024 * 1024;
            $currentBytes = (int) Document::query()
                ->where('owner_id', $this->user()->id)
                ->sum('size');
            $incomingBytes = (int) ($this->file('file')?->getSize() ?? 0);

            if (($currentBytes - $currentDocumentSize + $incomingBytes) > $quotaBytes) {
                $validator->errors()->add('file', 'Storage quota exceeded for current plan.');
            }
        });
    }
}
