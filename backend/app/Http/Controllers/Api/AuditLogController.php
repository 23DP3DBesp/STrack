<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $logs = $document->auditLogs()
            ->with('user:id,name,email')
            ->latest()
            ->limit(50)
            ->get();

        return response()->json($logs);
    }
}
