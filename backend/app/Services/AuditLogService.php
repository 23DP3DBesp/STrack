<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;

class AuditLogService
{
    public function log(Document $document, ?User $user, string $action, array $meta = []): AuditLog
    {
        return AuditLog::query()->create([
            'document_id' => $document->id,
            'user_id' => $user?->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }
}
