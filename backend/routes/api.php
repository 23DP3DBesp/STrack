<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentShareController;
use App\Http\Controllers\Api\DocumentVersionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeveloperController;
use App\Http\Controllers\Api\FolderController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DocumentCommentController;
use App\Http\Controllers\Api\RealtimeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/realtime/feed', [RealtimeController::class, 'feed']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read']);

    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/trash', [DocumentController::class, 'trash']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::put('/documents/{document}', [DocumentController::class, 'update']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
    Route::post('/documents/bulk', [DocumentController::class, 'bulk']);
    Route::post('/documents/{document}/duplicate', [DocumentController::class, 'duplicate']);
    Route::post('/documents/{document}/star', [DocumentController::class, 'star']);
    Route::post('/documents/{documentId}/restore', [DocumentController::class, 'restore']);
    Route::delete('/documents/{documentId}/purge', [DocumentController::class, 'forceDelete']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview']);
    Route::get('/documents/{document}/content', [DocumentController::class, 'content']);
    Route::put('/documents/{document}/content', [DocumentController::class, 'updateContent']);
    Route::get('/documents/{document}/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/documents/{document}/comments', [DocumentCommentController::class, 'index']);
    Route::post('/documents/{document}/comments', [DocumentCommentController::class, 'store']);
    Route::delete('/documents/{document}/comments/{comment}', [DocumentCommentController::class, 'destroy']);

    Route::get('/documents/{document}/versions', [DocumentVersionController::class, 'index']);
    Route::post('/documents/{document}/versions/{version}/restore', [DocumentVersionController::class, 'restore']);

    Route::get('/documents/{document}/shares', [DocumentShareController::class, 'index']);
    Route::post('/documents/{document}/shares', [DocumentShareController::class, 'store']);
    Route::put('/documents/{document}/shares/{user}', [DocumentShareController::class, 'update']);
    Route::delete('/documents/{document}/shares/{user}', [DocumentShareController::class, 'destroy']);

    Route::get('/folders', [FolderController::class, 'index']);
    Route::post('/folders', [FolderController::class, 'store']);
    Route::post('/folders/bulk', [FolderController::class, 'bulk']);
    Route::post('/folders/{folder}/duplicate', [FolderController::class, 'duplicate']);
    Route::put('/folders/{folder}', [FolderController::class, 'update']);
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy']);

    Route::get('/users/search', function (Request $request) {
        $query = trim((string) $request->query('q', ''));

        return \App\Models\User::query()
            ->when($query !== '', fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%"))
            ->limit(20)
            ->get(['id', 'name', 'email']);
    });

    Route::prefix('/admin')->middleware('admin')->group(function (): void {
        Route::get('/summary', [AdminController::class, 'summary']);
        Route::get('/developer/overview', [AdminController::class, 'developerOverview']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole']);
        Route::get('/trash', [AdminController::class, 'trash']);
        Route::post('/documents/{documentId}/restore', [AdminController::class, 'restoreDocument']);
        Route::delete('/documents/{documentId}/purge', [AdminController::class, 'purgeDocument']);
    });

    Route::prefix('/developer')->middleware('staff')->group(function (): void {
        Route::get('/overview', [DeveloperController::class, 'overview']);
        Route::get('/users', [DeveloperController::class, 'users']);
        Route::put('/users/{user}/role', [DeveloperController::class, 'updateUserRole']);
        Route::post('/users/{user}/impersonate', [DeveloperController::class, 'impersonate']);
        Route::post('/users/{user}/reset-password', [DeveloperController::class, 'resetUserPassword']);
        Route::post('/users/{user}/notifications', [DeveloperController::class, 'sendNotification']);
        Route::get('/users/{userId}/documents', [DeveloperController::class, 'userDocuments']);
        Route::get('/activity', [DeveloperController::class, 'activity']);
        Route::get('/notifications', [DeveloperController::class, 'notifications']);
        Route::get('/documents', [DeveloperController::class, 'documents']);
        Route::get('/documents/{documentId}', [DeveloperController::class, 'documentDetails']);
        Route::get('/documents/{documentId}/shares', [DeveloperController::class, 'documentShares']);
        Route::get('/documents/{documentId}/comments', [DeveloperController::class, 'documentComments']);
        Route::get('/documents/{documentId}/audit', [DeveloperController::class, 'documentAudit']);
        Route::delete('/documents/{documentId}/shares/{user}', [DeveloperController::class, 'removeShare']);
        Route::delete('/documents/{documentId}/comments/{comment}', [DeveloperController::class, 'deleteComment']);
        Route::post('/documents/{documentId}/restore', [DeveloperController::class, 'documentRestore']);
        Route::delete('/documents/{documentId}/purge', [DeveloperController::class, 'documentPurge']);
        Route::post('/documents/{documentId}/archive', [DeveloperController::class, 'documentArchive']);
        Route::post('/documents/{documentId}/star', [DeveloperController::class, 'documentStar']);
        Route::post('/documents/{documentId}/reassign-owner', [DeveloperController::class, 'reassignOwner']);
        Route::post('/documents/bulk', [DeveloperController::class, 'bulkDocumentsAction']);
        Route::get('/storage/top-users', [DeveloperController::class, 'storageTopUsers']);
        Route::post('/system/broadcast', [DeveloperController::class, 'broadcastNotification']);
        Route::post('/system/cleanup-trash', [DeveloperController::class, 'cleanupOldTrash']);
    });
});
