<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentCommentController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $comments = $document->comments()->with('user:id,name,email')->get();

        return response()->json($comments);
    }

    public function store(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $comment = DocumentComment::query()->create([
            'document_id' => $document->id,
            'user_id' => $request->user()->id,
            'content' => trim((string) $validated['content']),
        ]);

        $recipientIds = $document->collaborators()->pluck('users.id')->push($document->owner_id)
            ->filter(fn ($id) => (int) $id !== (int) $request->user()->id)
            ->unique()
            ->values()
            ->all();

        $this->notificationService->notifyUsers(
            $recipientIds,
            'comment.created',
            'New comment',
            "{$request->user()->name} commented on {$document->title}",
            [
                'document_id' => $document->id,
                'comment_id' => $comment->id,
            ]
        );

        return response()->json($comment->load('user:id,name,email'), 201);
    }

    public function destroy(Request $request, Document $document, DocumentComment $comment): JsonResponse
    {
        $this->authorize('view', $document);
        abort_unless($comment->document_id === $document->id, 404);

        $isOwner = (int) $comment->user_id === (int) $request->user()->id;
        $canModerate = $request->user()->isStaff() || (int) $document->owner_id === (int) $request->user()->id;
        abort_unless($isOwner || $canModerate, 403, 'Cannot delete this comment');

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
