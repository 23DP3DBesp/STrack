<?php

namespace App\Http\Requests;

use App\Models\Document;
use App\Services\FileSecurityService;
use App\Services\SecurityLogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxFileKb = max(1, (int) config('docbox.upload.max_file_kb', 102400));
        $allowedExtensions = array_values(array_filter((array) config('docbox.upload.allowed_extensions', [])));
        $allowedMimeTypes = array_values(array_filter((array) config('docbox.upload.allowed_mime_types', [])));

        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'file' => [
                'required',
                'file',
                'max:'.$maxFileKb,
                ...(! empty($allowedExtensions) ? ['mimes:'.implode(',', $allowedExtensions)] : []),
                ...(! empty($allowedMimeTypes) ? ['mimetypes:'.implode(',', $allowedMimeTypes)] : []),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasFile('file') || ! $this->user()) {
                return;
            }
            if (in_array($this->user()->role, ['admin', 'manager'], true)) {
                return;
            }

            $quotaMb = max(1, (int) config('docbox.upload.free_plan_quota_mb', 512));
            $quotaBytes = $quotaMb * 1024 * 1024;
            $currentBytes = (int) Document::query()
                ->where('owner_id', $this->user()->id)
                ->sum('size');
            $incomingBytes = (int) ($this->file('file')?->getSize() ?? 0);

            if (($currentBytes + $incomingBytes) > $quotaBytes) {
                $validator->errors()->add('file', 'Storage quota exceeded for current plan.');
            }

            $scan = app(FileSecurityService::class)->scanUploadedFile($this->file('file'));
            if (($scan['status'] ?? '') === 'infected') {
                app(SecurityLogService::class)->log('upload.virus_detected', [
                    'user_id' => $this->user()->id,
                    'file_name' => $this->file('file')?->getClientOriginalName(),
                    'ip' => (string) $this->ip(),
                ]);
                $validator->errors()->add('file', 'File failed security scan.');
            } elseif (($scan['status'] ?? '') === 'error') {
                app(SecurityLogService::class)->log('upload.virus_scan_error', [
                    'user_id' => $this->user()->id,
                    'file_name' => $this->file('file')?->getClientOriginalName(),
                    'ip' => (string) $this->ip(),
                    'reason' => (string) ($scan['reason'] ?? 'unknown'),
                ]);
            }
        });
    }
}
