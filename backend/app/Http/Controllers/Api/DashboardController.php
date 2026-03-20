<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $user = request()->user();

        $accessibleDocuments = Document::query()
            ->where(function ($query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            });

        $stats = [
            'documents_total' => (clone $accessibleDocuments)->count(),
            'documents_owned' => Document::query()->where('owner_id', $user->id)->count(),
            'documents_shared_with_me' => (clone $accessibleDocuments)->where('owner_id', '!=', $user->id)->count(),
            'documents_archived' => (clone $accessibleDocuments)->where('is_archived', true)->count(),
            'documents_in_trash' => Document::query()
                ->onlyTrashed()
                ->where(function ($query) use ($user): void {
                    $query->where('owner_id', $user->id)
                        ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
                })
                ->count(),
            'folders_total' => Folder::query()->where('owner_id', $user->id)->count(),
        ];

        $recentDocuments = (clone $accessibleDocuments)
            ->with(['owner:id,name,email', 'folder:id,name'])
            ->latest()
            ->limit(6)
            ->get(['id', 'owner_id', 'folder_id', 'title', 'updated_at', 'mime_type', 'size']);

        $recentActivity = AuditLog::query()
            ->whereHas('document', function ($query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->with(['user:id,name,email', 'document:id,title'])
            ->latest()
            ->limit(8)
            ->get(['id', 'document_id', 'user_id', 'action', 'created_at']);

        return response()->json([
            'stats' => $stats,
            'recent_documents' => $recentDocuments,
            'recent_activity' => $recentActivity,
        ]);
    }
}
