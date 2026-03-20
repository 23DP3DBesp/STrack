<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    public function createDocument(User $user, string $title, ?string $description, ?int $folderId, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($user, $title, $description, $folderId, $file): Document {
            $document = Document::query()->create([
                'owner_id' => $user->id,
                'folder_id' => $folderId,
                'title' => $title,
                'description' => $description,
                'path' => '',
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'size' => (int) $file->getSize(),
                'is_archived' => false,
            ]);

            $version = $this->createVersion($document, $file, $user, 1);

            $document->update([
                'path' => $version->path,
                'current_version_id' => $version->id,
            ]);

            return $document->load(['owner', 'folder', 'currentVersion']);
        });
    }

    public function updateDocument(Document $document, User $user, array $attributes, ?UploadedFile $file = null): Document
    {
        return DB::transaction(function () use ($document, $user, $attributes, $file): Document {
            $document->fill($attributes);

            if ($file) {
                $nextVersion = ((int) $document->versions()->max('version_number')) + 1;
                $version = $this->createVersion($document, $file, $user, $nextVersion);
                $document->path = $version->path;
                $document->mime_type = $version->mime_type;
                $document->size = $version->size;
                $document->current_version_id = $version->id;
            }

            $document->save();

            return $document->load(['owner', 'folder', 'currentVersion']);
        });
    }

    public function restoreVersion(Document $document, DocumentVersion $version): Document
    {
        $document->update([
            'path' => $version->path,
            'mime_type' => $version->mime_type,
            'size' => $version->size,
            'current_version_id' => $version->id,
        ]);

        return $document->load(['owner', 'folder', 'currentVersion']);
    }

    public function updateDocumentContent(Document $document, User $user, string $content): Document
    {
        return DB::transaction(function () use ($document, $user, $content): Document {
            $nextVersion = ((int) $document->versions()->max('version_number')) + 1;
            $version = $this->createVersionFromContent($document, $user, $nextVersion, $content);

            $document->update([
                'path' => $version->path,
                'mime_type' => $version->mime_type,
                'size' => $version->size,
                'current_version_id' => $version->id,
            ]);

            return $document->load(['owner', 'folder', 'currentVersion']);
        });
    }

    public function duplicateDocument(Document $document, User $user, ?int $folderId = null, ?string $title = null): Document
    {
        return DB::transaction(function () use ($document, $user, $folderId, $title): Document {
            $sourceVersion = $document->currentVersion;
            abort_unless($sourceVersion, 404, 'Source version not found');

            $resolvedTitle = $title && trim($title) !== '' ? trim($title) : $this->buildDuplicateTitle($document->title);

            $duplicate = Document::query()->create([
                'owner_id' => $user->id,
                'folder_id' => $folderId ?? $document->folder_id,
                'title' => $resolvedTitle,
                'description' => $document->description,
                'path' => '',
                'mime_type' => $sourceVersion->mime_type,
                'size' => $sourceVersion->size,
                'is_archived' => false,
                'is_starred' => false,
            ]);

            $folder = 'documents/'.$duplicate->id;
            $ext = pathinfo($sourceVersion->path, PATHINFO_EXTENSION);
            $filename = Str::uuid()->toString().($ext ? '.'.$ext : '');
            $path = $folder.'/'.$filename;
            $content = Storage::disk('private')->get($sourceVersion->path);
            Storage::disk('private')->put($path, $content);

            $version = DocumentVersion::query()->create([
                'document_id' => $duplicate->id,
                'version_number' => 1,
                'path' => $path,
                'mime_type' => $sourceVersion->mime_type ?: 'application/octet-stream',
                'size' => strlen($content),
                'checksum' => hash('sha256', $content),
                'uploaded_by' => $user->id,
            ]);

            $duplicate->update([
                'path' => $version->path,
                'current_version_id' => $version->id,
            ]);

            return $duplicate->load(['owner', 'folder', 'currentVersion']);
        });
    }

    private function createVersion(Document $document, UploadedFile $file, User $user, int $number): DocumentVersion
    {
        $folder = 'documents/'.$document->id;
        $ext = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($ext ? '.'.$ext : '');
        $path = $file->storeAs($folder, $filename, 'private');
        $content = Storage::disk('private')->get($path);

        return DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $number,
            'path' => $path,
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'checksum' => hash('sha256', $content),
            'uploaded_by' => $user->id,
        ]);
    }

    private function createVersionFromContent(Document $document, User $user, int $number, string $content): DocumentVersion
    {
        $folder = 'documents/'.$document->id;
        $ext = pathinfo((string) $document->path, PATHINFO_EXTENSION);
        $filename = Str::uuid()->toString().($ext ? '.'.$ext : '.txt');
        $path = $folder.'/'.$filename;
        Storage::disk('private')->put($path, $content);

        return DocumentVersion::query()->create([
            'document_id' => $document->id,
            'version_number' => $number,
            'path' => $path,
            'mime_type' => $document->mime_type ?: 'text/plain',
            'size' => strlen($content),
            'checksum' => hash('sha256', $content),
            'uploaded_by' => $user->id,
        ]);
    }

    private function buildDuplicateTitle(string $title): string
    {
        if (str_ends_with($title, ' (Copy)')) {
            return $title.' 2';
        }

        return $title.' (Copy)';
    }
}
