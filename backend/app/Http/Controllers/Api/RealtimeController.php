<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $sinceAuditId = max(0, (int) $request->query('since_audit_id', 0));
        $sinceNotificationId = max(0, (int) $request->query('since_notification_id', 0));

        $visibleDocumentIds = Document::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $user->id));
            })
            ->pluck('id');

        $events = AuditLog::query()
            ->with(['user:id,name', 'document:id,title'])
            ->whereIn('document_id', $visibleDocumentIds)
            ->where('id', '>', $sinceAuditId)
            ->orderBy('id')
            ->limit(80)
            ->get();

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->where('id', '>', $sinceNotificationId)
            ->orderBy('id')
            ->limit(80)
            ->get();

        return response()->json([
            'events' => $events,
            'notifications' => $notifications,
            'latest_audit_id' => (int) ($events->last()->id ?? $sinceAuditId),
            'latest_notification_id' => (int) ($notifications->last()->id ?? $sinceNotificationId),
            'unread_notifications' => UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
