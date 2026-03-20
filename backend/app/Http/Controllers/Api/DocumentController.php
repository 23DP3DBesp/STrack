<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDocumentActionRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentContentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Services\AuditLogService;
use App\Services\DocumentService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $search = trim((string) $request->query('q', ''));
        $folderId = $request->query('folder_id');
        $isStarred = $request->query('is_starred');
        $sortBy = (string) $request->query('sort_by', 'updated_at');
        $sortDir = (string) $request->query('sort_dir', 'desc');
        $ownership = (string) $request->query('ownership', 'all');
        $sizeFromKb = (int) $request->query('size_from_kb', 0);
        $isArchived = $request->query('is_archived');
        $isArchivedFilter = null;
        $isStarredFilter = null;
        if ($isArchived !== null && $isArchived !== '') {
            $parsed = filter_var($isArchived, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $isArchivedFilter = $parsed ?? ((string) $isArchived === '1');
        }
        if ($isStarred !== null && $isStarred !== '') {
            $parsed = filter_var($isStarred, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            $isStarredFilter = $parsed ?? ((string) $isStarred === '1');
        }

        $allowedSort = ['updated_at', 'created_at', 'title', 'size'];
        $sortBy = in_array($sortBy, $allowedSort, true) ? $sortBy : 'updated_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc'], true) ? strtolower($sortDir) : 'desc';

        $documents = Document::query()
            ->with(['owner', 'folder', 'currentVersion'])
            ->where(function ($query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->when($ownership === 'owned', fn ($query) => $query->where('owner_id', $user->id))
            ->when($ownership === 'shared', fn ($query) => $query->where('owner_id', '!=', $user->id))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($folderId !== null && $folderId !== '', fn ($query) => $query->where('folder_id', (int) $folderId))
            ->when($isArchivedFilter !== null, fn ($query) => $query->where('is_archived', $isArchivedFilter))
            ->when($isStarredFilter !== null, fn ($query) => $query->where('is_starred', $isStarredFilter))
            ->when($sizeFromKb > 0, fn ($query) => $query->where('size', '>=', $sizeFromKb * 1024))
            ->orderBy($sortBy, $sortDir)
            ->paginate(20);

        return response()->json($documents);
    }

    public function trash(Request $request): JsonResponse
    {
        $user = $request->user();

        $documents = Document::query()
            ->onlyTrashed()
            ->with(['owner:id,name,email', 'folder:id,name', 'currentVersion'])
            ->where(function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->latest('deleted_at')
            ->paginate(20);

        return response()->json($documents);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $folderId = $request->input('folder_id') !== null ? (int) $request->input('folder_id') : null;
        $this->assertFolderAccess($request->user()->id, $folderId);

        $document = $this->documentService->createDocument(
            $request->user(),
            (string) $request->input('title'),
            $request->input('description'),
            $folderId,
            $request->file('file')
        );
        $this->auditLogService->log($document, $request->user(), 'document.created', [
            'title' => $document->title,
        ]);

        return response()->json($document, 201);
    }

    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json($document->load(['owner', 'folder', 'currentVersion', 'collaborators']));
    }

    public function update(UpdateDocumentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $payload = $request->safe()->except(['file']);
        if (array_key_exists('folder_id', $payload)) {
            $this->assertFolderAccess($request->user()->id, $payload['folder_id'] !== null ? (int) $payload['folder_id'] : null);
        }

        $document = $this->documentService->updateDocument(
            $document,
            $request->user(),
            $payload,
            $request->file('file')
        );
        $this->auditLogService->log($document, $request->user(), 'document.updated', [
            'has_file' => $request->hasFile('file'),
        ]);

        return response()->json($document);
    }

    public function duplicate(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', 'exists:folders,id'],
            'title' => ['nullable', 'string', 'max:180'],
        ]);
        $folderId = array_key_exists('folder_id', $validated) ? (int) $validated['folder_id'] : null;
        if ($folderId) {
            $this->assertFolderAccess($request->user()->id, $folderId);
        }

        $duplicated = $this->documentService->duplicateDocument(
            $document,
            $request->user(),
            $folderId,
            $validated['title'] ?? null
        );
        $this->auditLogService->log($duplicated, $request->user(), 'document.duplicated', [
            'source_document_id' => $document->id,
        ]);

        return response()->json($duplicated, 201);
    }

    public function star(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $validated = $request->validate([
            'is_starred' => ['required', 'boolean'],
        ]);

        $document->update([
            'is_starred' => (bool) $validated['is_starred'],
        ]);
        $this->auditLogService->log($document, $request->user(), 'document.star_updated', [
            'is_starred' => $document->is_starred,
        ]);

        return response()->json($document->load(['owner', 'folder', 'currentVersion']));
    }

    public function destroy(Document $document): JsonResponse
    {
        $user = request()->user();
        $this->authorize('delete', $document);
        $this->auditLogService->log($document, $user, 'document.moved_to_trash');
        $recipientIds = $document->collaborators()->pluck('users.id')
            ->push($document->owner_id)
            ->filter(fn ($id) => (int) $id !== (int) $user->id)
            ->unique()
            ->values()
            ->all();
        $this->notificationService->notifyUsers(
            $recipientIds,
            'document.trashed',
            'Document moved to trash',
            "{$user->name} moved {$document->title} to trash",
            ['document_id' => $document->id]
        );
        $document->delete();

        return response()->json(['message' => 'Document moved to trash']);
    }

    public function restore(Request $request, int $documentId): JsonResponse
    {
        $document = $this->resolveDocumentWithTrash($request, $documentId);
        abort_if(!$document->trashed(), 422, 'Document is not in trash');
        $this->authorize('delete', $document);

        $document->restore();
        $this->auditLogService->log($document, $request->user(), 'document.restored');

        return response()->json([
            'message' => 'Document restored',
            'document' => $document->load(['owner', 'folder', 'currentVersion']),
        ]);
    }

    public function forceDelete(Request $request, int $documentId): JsonResponse
    {
        $document = $this->resolveDocumentWithTrash($request, $documentId);
        $this->authorize('delete', $document);

        $recipientIds = $document->collaborators()->pluck('users.id')
            ->push($document->owner_id)
            ->filter(fn ($id) => (int) $id !== (int) $request->user()->id)
            ->unique()
            ->values()
            ->all();
        $this->auditLogService->log($document, $request->user(), 'document.deleted_permanently');
        $document->versions()->each(function ($version): void {
            Storage::disk('private')->delete($version->path);
        });
        $document->forceDelete();
        $this->notificationService->notifyUsers(
            $recipientIds,
            'document.deleted',
            'Document deleted permanently',
            $request->user()->name.' deleted a document permanently',
            ['document_id' => $documentId]
        );

        return response()->json(['message' => 'Document permanently deleted']);
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);

        $version = $document->currentVersion;

        abort_unless($version, 404);

        return Storage::disk('private')->download($version->path, $document->title, [
            'Content-Type' => $version->mime_type,
        ]);
    }

    public function preview(Document $document)
    {
        $this->authorize('view', $document);

        $version = $document->currentVersion;
        abort_unless($version, 404);

        return Storage::disk('private')->response($version->path, null, [
            'Content-Type' => $version->mime_type,
            'Content-Disposition' => 'inline',
        ]);
    }

    public function content(Document $document): JsonResponse
    {
        $this->authorize('view', $document);
        abort_unless($this->isEditableMime($document->mime_type), 422, 'Document is not editable');

        $content = Storage::disk('private')->exists($document->path)
            ? Storage::disk('private')->get($document->path)
            : '';

        return response()->json([
            'content' => $content,
            'mime_type' => $document->mime_type,
            'editable' => true,
        ]);
    }

    public function updateContent(UpdateDocumentContentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);
        abort_unless($this->isEditableMime($document->mime_type), 422, 'Document is not editable');

        $updated = $this->documentService->updateDocumentContent(
            $document,
            $request->user(),
            (string) $request->input('content')
        );
        $this->auditLogService->log($document, $request->user(), 'document.content_updated', [
            'size' => $updated->size,
        ]);

        return response()->json($updated);
    }

    public function bulk(BulkDocumentActionRequest $request): JsonResponse
    {
        $user = $request->user();
        $action = (string) $request->input('action');
        $targetFolderId = $request->input('folder_id') !== null ? (int) $request->input('folder_id') : null;
        $ids = collect($request->input('document_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $includeTrashed = in_array($action, ['restore', 'purge'], true);
        $query = Document::query();
        if ($includeTrashed) {
            $query->withTrashed();
        }

        $documents = $query->whereIn('id', $ids)->get()->keyBy('id');

        $processed = 0;
        $skipped = [];

        foreach ($ids as $id) {
            /** @var Document|null $document */
            $document = $documents->get($id);
            if (!$document) {
                $skipped[] = $id;
                continue;
            }

            try {
                match ($action) {
                    'archive' => $this->bulkArchive($user, $document, true),
                    'unarchive' => $this->bulkArchive($user, $document, false),
                    'trash' => $this->bulkTrash($user, $document),
                    'restore' => $this->bulkRestore($user, $document),
                    'purge' => $this->bulkPurge($user, $document),
                    'star' => $this->bulkStar($user, $document, true),
                    'unstar' => $this->bulkStar($user, $document, false),
                    'move' => $this->bulkMove($user, $document, $targetFolderId),
                    default => null,
                };
                $processed++;
            } catch (\Throwable) {
                $skipped[] = $id;
            }
        }

        return response()->json([
            'action' => $action,
            'processed' => $processed,
            'skipped' => $skipped,
        ]);
    }

    private function assertFolderAccess(int $userId, ?int $folderId): void
    {
        if ($folderId === null) {
            return;
        }

        $exists = Folder::query()->where('id', $folderId)->where('owner_id', $userId)->exists();
        abort_unless($exists, 422, 'Invalid folder');
    }

    private function resolveDocumentWithTrash(Request $request, int $documentId): Document
    {
        $user = $request->user();
        $query = Document::query()->withTrashed()->where('id', $documentId);

        if ($user->isStaff()) {
            return $query->firstOrFail();
        }

        return $query
            ->where(function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->firstOrFail();
    }

    private function isEditableMime(?string $mimeType): bool
    {
        if (!$mimeType) {
            return false;
        }

        $editable = [
            'text/plain',
            'text/markdown',
            'application/json',
            'text/csv',
            'application/xml',
            'text/xml',
        ];

        if (str_starts_with($mimeType, 'text/')) {
            return true;
        }

        return in_array($mimeType, $editable, true);
    }

    private function bulkArchive($user, Document $document, bool $archived): void
    {
        $this->authorize('update', $document);
        $document->update(['is_archived' => $archived]);
        $this->auditLogService->log($document, $user, $archived ? 'document.archived' : 'document.unarchived');
    }

    private function bulkTrash($user, Document $document): void
    {
        $this->authorize('delete', $document);
        if ($document->trashed()) {
            return;
        }
        $document->delete();
        $this->auditLogService->log($document, $user, 'document.moved_to_trash');
    }

    private function bulkRestore($user, Document $document): void
    {
        $this->authorize('delete', $document);
        if (!$document->trashed()) {
            return;
        }
        $document->restore();
        $this->auditLogService->log($document, $user, 'document.restored');
    }

    private function bulkPurge($user, Document $document): void
    {
        $this->authorize('delete', $document);
        $this->auditLogService->log($document, $user, 'document.deleted_permanently');
        $document->versions()->each(function ($version): void {
            Storage::disk('private')->delete($version->path);
        });
        $document->forceDelete();
    }

    private function bulkStar($user, Document $document, bool $starred): void
    {
        $this->authorize('view', $document);
        $document->update(['is_starred' => $starred]);
        $this->auditLogService->log($document, $user, 'document.star_updated', [
            'is_starred' => $starred,
        ]);
    }

    private function bulkMove($user, Document $document, ?int $folderId): void
    {
        $this->authorize('update', $document);
        $this->assertFolderAccess($user->id, $folderId);
        $document->update(['folder_id' => $folderId]);
        $this->auditLogService->log($document, $user, 'document.moved', [
            'folder_id' => $folderId,
        ]);
    }
}
