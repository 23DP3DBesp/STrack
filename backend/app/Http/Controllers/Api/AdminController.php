<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function summary(): JsonResponse
    {
        return response()->json([
            'users_total' => User::query()->count(),
            'admins_total' => User::query()->where('role', 'admin')->count(),
            'developers_total' => User::query()->where('role', 'developer')->count(),
            'documents_total' => Document::query()->count(),
            'documents_in_trash' => Document::query()->onlyTrashed()->count(),
            'storage_total_bytes' => (int) Document::query()->sum('size'),
        ]);
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
            ->paginate(20);

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

    public function trash(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $documents = Document::query()
            ->onlyTrashed()
            ->with(['owner:id,name,email', 'folder:id,name', 'currentVersion'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest('deleted_at')
            ->paginate(30);

        return response()->json($documents);
    }

    public function restoreDocument(int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);
        abort_if(!$document->trashed(), 422, 'Document is not in trash');

        $document->restore();

        return response()->json([
            'message' => 'Document restored',
            'document' => $document->load(['owner', 'folder', 'currentVersion']),
        ]);
    }

    public function purgeDocument(int $documentId): JsonResponse
    {
        $document = Document::query()->withTrashed()->findOrFail($documentId);

        $document->versions()->each(function ($version): void {
            Storage::disk('private')->delete($version->path);
        });

        $document->forceDelete();

        return response()->json(['message' => 'Document permanently deleted']);
    }

    public function developerOverview(): JsonResponse
    {
        $privateDisk = Storage::disk('private');
        $allDocuments = Document::query()->withTrashed()->count();
        $allVersions = DB::table('document_versions')->count();
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'url' => config('app.url'),
            ],
            'runtime' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'timezone' => config('app.timezone'),
            ],
            'storage' => [
                'private_disk' => config('filesystems.default') === 'private' ? 'default' : 'private',
                'documents_total' => $allDocuments,
                'versions_total' => $allVersions,
                'failed_jobs' => $failedJobs,
                'free_plan_quota_mb' => (int) config('docbox.upload.free_plan_quota_mb', 512),
                'max_file_kb' => (int) config('docbox.upload.max_file_kb', 102400),
                'sample_file_exists' => $privateDisk->exists('documents'),
            ],
            'database' => [
                'default' => config('database.default'),
                'driver' => config('database.connections.'.config('database.default').'.driver'),
                'database' => config('database.connections.'.config('database.default').'.database'),
            ],
        ]);
    }
}
