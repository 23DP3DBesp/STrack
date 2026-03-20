<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShareDocumentRequest;
use App\Http\Requests\UpdateShareRequest;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class DocumentShareController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly NotificationService $notificationService
    )
    {
    }

    public function index(Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        $shares = $document->collaborators()->get(['users.id', 'users.name', 'users.email', 'permission']);

        return response()->json($shares);
    }

    public function store(ShareDocumentRequest $request, Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        abort_if($request->integer('user_id') === $document->owner_id, 422, 'Owner already has full access');

        $document->collaborators()->syncWithoutDetaching([
            $request->integer('user_id') => ['permission' => (string) $request->input('permission')],
        ]);
        $this->auditLogService->log($document, $request->user(), 'access.granted', [
            'target_user_id' => $request->integer('user_id'),
            'permission' => (string) $request->input('permission'),
        ]);
        $this->notificationService->notifyUser(
            $request->integer('user_id'),
            'share.granted',
            'Document shared with you',
            "{$request->user()->name} granted you {$request->input('permission')} access to {$document->title}",
            [
                'document_id' => $document->id,
                'permission' => (string) $request->input('permission'),
            ]
        );

        return response()->json(['message' => 'Access granted']);
    }

    public function update(UpdateShareRequest $request, Document $document, User $user): JsonResponse
    {
        $this->authorize('share', $document);

        $document->collaborators()->updateExistingPivot($user->id, [
            'permission' => (string) $request->input('permission'),
        ]);
        $this->auditLogService->log($document, $request->user(), 'access.updated', [
            'target_user_id' => $user->id,
            'permission' => (string) $request->input('permission'),
        ]);
        $this->notificationService->notifyUser(
            $user->id,
            'share.permission_updated',
            'Document permission updated',
            "{$request->user()->name} changed your permission on {$document->title} to {$request->input('permission')}",
            [
                'document_id' => $document->id,
                'permission' => (string) $request->input('permission'),
            ]
        );

        return response()->json(['message' => 'Access updated']);
    }

    public function destroy(Document $document, User $user): JsonResponse
    {
        $this->authorize('share', $document);

        $document->collaborators()->detach($user->id);
        $this->auditLogService->log($document, request()->user(), 'access.revoked', [
            'target_user_id' => $user->id,
        ]);
        $this->notificationService->notifyUser(
            $user->id,
            'share.revoked',
            'Document access removed',
            request()->user()->name." removed your access to {$document->title}",
            [
                'document_id' => $document->id,
            ]
        );

        return response()->json(['message' => 'Access removed']);
    }
}
