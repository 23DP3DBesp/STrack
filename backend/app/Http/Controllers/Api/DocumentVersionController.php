<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditLogService;
use App\Services\DocumentService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class DocumentVersionController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService
    )
    {
    }

    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $versions = $document->versions()
            ->with('uploader:id,name,email')
            ->latest('version_number')
            ->get();

        return response()->json($versions);
    }

    public function restore(Document $document, DocumentVersion $version): JsonResponse
    {
        $this->authorize('update', $document);

        abort_unless($version->document_id === $document->id, 404);

        $updated = $this->documentService->restoreVersion($document, $version);
        $this->auditLogService->log($document, request()->user(), 'version.restored', [
            'version_number' => $version->version_number,
        ]);
        $recipientIds = $document->collaborators()->pluck('users.id')
            ->push($document->owner_id)
            ->filter(fn ($id) => (int) $id !== (int) request()->user()->id)
            ->unique()
            ->values()
            ->all();
        $this->notificationService->notifyUsers(
            $recipientIds,
            'version.restored',
            'Version restored',
            request()->user()->name." restored version {$version->version_number} of {$document->title}",
            [
                'document_id' => $document->id,
                'version_id' => $version->id,
            ]
        );

        return response()->json($updated);
    }
}
