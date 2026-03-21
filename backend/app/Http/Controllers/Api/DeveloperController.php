<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PurgeDocumentJob;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Services\SecurityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DeveloperController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly SecurityLogService $securityLogService
    ) {}

    public function overview(): JsonResponse
    {
        $ttl = max(1, (int) config('docbox.cache_ttl_seconds', 60));
        $payload = Cache::remember('developer.overview', $ttl, static function (): array {
            $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $notifications = Schema::hasTable('user_notifications') ? DB::table('user_notifications')->count() : 0;
            $comments = Schema::hasTable('document_comments') ? DB::table('document_comments')->count() : 0;

            return [
                'users' => [
                    'total' => User::query()->count(),
                    'admins' => User::query()->where('role', 'admin')->count(),
                    'developers' => User::query()->where('role', 'developer')->count(),
                ],
                'documents' => [
                    'total' => Document::query()->count(),
                    'in_trash' => Document::query()->onlyTrashed()->count(),
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'failed_jobs' => $failedJobs,
                    'notifications_total' => $notifications,
                    'comments_total' => $comments,
                ],
            ];
        });

        return response()->json($payload);
    }

    public function users(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withCount(['folders', 'sharedDocuments'])
            ->latest()
            ->paginate(25);

        if (Schema::hasTable('user_notifications')) {
            $users->getCollection()->loadCount('notifications');
        }

        return response()->json($users);
    }

    public function updateUserRole(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'developer', 'manager', 'user'])],
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Role updated',
            'user' => $user,
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $events = AuditLog::query()
            ->with(['user:id,name,email', 'document:id,title'])
            ->latest()
            ->paginate((int) $request->query('per_page', 30));

        return response()->json($events);
    }

    public function notifications(Request $request): JsonResponse
    {
        $items = UserNotification::query()
            ->with('user:id,name,email')
            ->latest()
            ->paginate((int) $request->query('per_page', 30));

        return response()->json($items);
    }

    public function documents(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $ownerId = (int) $request->query('owner_id', 0);
        $onlyTrashed = filter_var($request->query('only_trashed', false), FILTER_VALIDATE_BOOL);
        $archived = $request->query('is_archived');

        $query = Document::query();
        if ($onlyTrashed) {
            $query->onlyTrashed();
        } else {
            $query->withTrashed();
        }

        $items = $query
            ->with(['owner:id,name,email', 'folder:id,name', 'currentVersion'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($ownerId > 0, fn (Builder $query) => $query->where('owner_id', $ownerId))
            ->when($archived !== null && $archived !== '', fn (Builder $query) => $query->where('is_archived', filter_var($archived, FILTER_VALIDATE_BOOL)))
            ->latest('updated_at')
            ->paginate((int) $request->query('per_page', 25));

        return response()->json($items);
    }

    public function documentDetails(int $documentId): JsonResponse
    {
        $document = Document::query()
            ->withTrashed()
            ->with(['owner:id,name,email', 'folder:id,name', 'currentVersion', 'collaborators:id,name,email'])
            ->findOrFail($documentId);

        return response()->json($document);
    }

    public function documentShares(int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $shares = $document->collaborators()->get(['users.id', 'users.name', 'users.email', 'permission']);

        return response()->json($shares);
    }

    public function documentComments(int $documentId): JsonResponse
    {
        $comments = DocumentComment::query()
            ->where('document_id', $documentId)
            ->with('user:id,name,email')
            ->latest()
            ->get();

        return response()->json($comments);
    }

    public function documentAudit(int $documentId): JsonResponse
    {
        $logs = AuditLog::query()
            ->where('document_id', $documentId)
            ->with(['user:id,name,email'])
            ->latest()
            ->limit(200)
            ->get();

        return response()->json($logs);
    }

    public function documentRestore(int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        abort_if(! $document->trashed(), 422, 'Document is not in trash');

        $document->restore();

        return response()->json(['message' => 'Document restored']);
    }

    public function documentPurge(int $documentId): JsonResponse
    {
        Document::query()->withTrashed()->findOrFail($documentId);
        PurgeDocumentJob::dispatchSync($documentId);
        $this->securityLogService->log('developer.document_purged', [
            'actor_id' => (int) auth()->id(),
            'document_id' => $documentId,
            'ip' => (string) request()->ip(),
        ]);

        return response()->json(['message' => 'Document purged']);
    }

    public function documentArchive(Request $request, int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $validated = $request->validate([
            'is_archived' => ['required', 'boolean'],
        ]);
        $document->update(['is_archived' => (bool) $validated['is_archived']]);

        return response()->json(['message' => 'Archive state updated']);
    }

    public function documentStar(Request $request, int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $validated = $request->validate([
            'is_starred' => ['required', 'boolean'],
        ]);
        $document->update(['is_starred' => (bool) $validated['is_starred']]);

        return response()->json(['message' => 'Star state updated']);
    }

    public function reassignOwner(Request $request, int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $validated = $request->validate([
            'owner_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $document->update(['owner_id' => (int) $validated['owner_id']]);

        return response()->json(['message' => 'Owner reassigned']);
    }

    public function userDocuments(int $userId): JsonResponse
    {
        $items = Document::query()
            ->withTrashed()
            ->with(['owner:id,name,email', 'folder:id,name', 'currentVersion'])
            ->where('owner_id', $userId)
            ->latest('updated_at')
            ->limit(200)
            ->get();

        return response()->json($items);
    }

    public function sendNotification(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:3000'],
            'type' => ['nullable', 'string', 'max:80'],
        ]);

        $notification = $this->notificationService->notifyUser(
            $user->id,
            (string) ($validated['type'] ?? 'developer.manual'),
            (string) $validated['title'],
            (string) $validated['message'],
            ['by' => auth()->id()]
        );

        return response()->json($notification, 201);
    }

    public function storageTopUsers(): JsonResponse
    {
        $items = Document::query()
            ->select('owner_id', DB::raw('SUM(size) as total_bytes'), DB::raw('COUNT(*) as documents_total'))
            ->groupBy('owner_id')
            ->orderByDesc('total_bytes')
            ->with('owner:id,name,email')
            ->limit(20)
            ->get();

        return response()->json($items);
    }

    public function impersonate(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        abort_if((int) $actor->id === (int) $user->id, 422, 'Cannot impersonate yourself');

        $this->securityLogService->log('developer.impersonation_started', [
            'actor_id' => $actor->id,
            'target_user_id' => $user->id,
            'ip' => (string) $request->ip(),
        ]);
        $token = $user->createToken('impersonated-by-'.$actor->id)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
            'impersonator' => [
                'id' => $actor->id,
                'email' => $actor->email,
                'role' => $actor->role,
            ],
        ]);
    }

    public function resetUserPassword(User $user): JsonResponse
    {
        $temporary = Str::random(14);

        $user->update([
            'password' => Hash::make($temporary),
        ]);

        return response()->json([
            'message' => 'Temporary password generated',
            'temporary_password' => $temporary,
        ]);
    }

    public function removeShare(int $documentId, User $user): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        $document->collaborators()->detach($user->id);

        return response()->json(['message' => 'Share removed']);
    }

    public function deleteComment(int $documentId, DocumentComment $comment): JsonResponse
    {
        abort_unless((int) $comment->document_id === (int) $documentId, 404);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }

    public function broadcastNotification(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:3000'],
            'type' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', Rule::in(['admin', 'developer', 'manager', 'user'])],
        ]);

        $recipientIds = User::query()
            ->when(! empty($validated['role']), fn (Builder $query) => $query->where('role', $validated['role']))
            ->pluck('id')
            ->all();

        $this->notificationService->notifyUsers(
            $recipientIds,
            (string) ($validated['type'] ?? 'developer.broadcast'),
            (string) $validated['title'],
            (string) $validated['message'],
            ['by' => auth()->id()]
        );

        return response()->json([
            'message' => 'Broadcast delivered',
            'recipients' => count($recipientIds),
        ]);
    }

    public function bulkDocumentsAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['archive', 'unarchive', 'star', 'unstar', 'restore', 'purge'])],
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['integer'],
        ]);

        $documents = Document::query()
            ->withTrashed()
            ->whereIn('id', $validated['document_ids'])
            ->get();

        $processed = 0;
        $purged = 0;
        foreach ($documents as $document) {
            if ($validated['action'] === 'archive') {
                $document->update(['is_archived' => true]);
                $processed++;

                continue;
            }
            if ($validated['action'] === 'unarchive') {
                $document->update(['is_archived' => false]);
                $processed++;

                continue;
            }
            if ($validated['action'] === 'star') {
                $document->update(['is_starred' => true]);
                $processed++;

                continue;
            }
            if ($validated['action'] === 'unstar') {
                $document->update(['is_starred' => false]);
                $processed++;

                continue;
            }
            if ($validated['action'] === 'restore') {
                if ($document->trashed()) {
                    $document->restore();
                }
                $processed++;

                continue;
            }
            if ($validated['action'] === 'purge') {
                PurgeDocumentJob::dispatch($document->id);
                $purged++;
                $processed++;
            }
        }

        if ($validated['action'] === 'purge' && $purged >= 10) {
            $this->securityLogService->log('developer.bulk_purge', [
                'actor_id' => (int) auth()->id(),
                'requested_ids' => $validated['document_ids'],
                'purged' => $purged,
                'ip' => (string) $request->ip(),
            ]);
        }

        return response()->json([
            'message' => 'Bulk action completed',
            'processed' => $processed,
        ]);
    }

    public function cleanupOldTrash(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $days = (int) ($validated['days'] ?? 30);
        $threshold = now()->subDays($days);

        $documents = Document::query()
            ->onlyTrashed()
            ->where('deleted_at', '<=', $threshold)
            ->get();

        $purged = 0;
        foreach ($documents as $document) {
            PurgeDocumentJob::dispatch($document->id);
            $purged++;
        }

        return response()->json([
            'message' => 'Old trash cleaned',
            'purged' => $purged,
            'days' => $days,
        ]);
    }
}
