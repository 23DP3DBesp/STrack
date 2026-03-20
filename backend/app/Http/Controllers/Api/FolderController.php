<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkFolderActionRequest;
use App\Http\Requests\FolderStoreRequest;
use App\Http\Requests\FolderUpdateRequest;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;

class FolderController extends Controller
{
    public function index(): JsonResponse
    {
        $folders = Folder::query()
            ->where('owner_id', auth()->id())
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }

    public function store(FolderStoreRequest $request): JsonResponse
    {
        $folder = Folder::query()->create([
            'owner_id' => $request->user()->id,
            'name' => (string) $request->input('name'),
        ]);

        return response()->json($folder, 201);
    }

    public function update(FolderUpdateRequest $request, Folder $folder): JsonResponse
    {
        abort_unless($folder->owner_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $folder->update([
            'name' => (string) $request->input('name'),
        ]);

        return response()->json($folder);
    }

    public function destroy(Folder $folder): JsonResponse
    {
        $user = request()->user();
        abort_unless($folder->owner_id === $user->id || $user->isAdmin(), 403);

        $folder->documents()->update(['folder_id' => null]);
        $folder->delete();

        return response()->json(['message' => 'Folder deleted']);
    }

    public function duplicate(Folder $folder): JsonResponse
    {
        $user = request()->user();
        abort_unless($folder->owner_id === $user->id || $user->isAdmin(), 403);

        $baseName = $folder->name.' (Copy)';
        $name = $baseName;
        $counter = 2;
        while (Folder::query()->where('owner_id', $folder->owner_id)->where('name', $name)->exists()) {
            $name = $baseName.' '.$counter;
            $counter++;
        }

        $duplicate = Folder::query()->create([
            'owner_id' => $folder->owner_id,
            'name' => $name,
        ]);

        return response()->json($duplicate->loadCount('documents'), 201);
    }

    public function bulk(BulkFolderActionRequest $request): JsonResponse
    {
        $user = $request->user();
        $action = (string) $request->input('action');
        $ids = collect($request->input('folder_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $folders = Folder::query()
            ->where('owner_id', $user->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $processed = 0;
        $skipped = [];

        foreach ($ids as $id) {
            $folder = $folders->get($id);
            if (!$folder) {
                $skipped[] = $id;
                continue;
            }

            try {
                if ($action === 'delete') {
                    $folder->documents()->update(['folder_id' => null]);
                    $folder->delete();
                } elseif ($action === 'duplicate') {
                    $baseName = $folder->name.' (Copy)';
                    $name = $baseName;
                    $counter = 2;
                    while (Folder::query()->where('owner_id', $folder->owner_id)->where('name', $name)->exists()) {
                        $name = $baseName.' '.$counter;
                        $counter++;
                    }
                    Folder::query()->create([
                        'owner_id' => $folder->owner_id,
                        'name' => $name,
                    ]);
                }
                $processed++;
            } catch (\Throwable) {
                $skipped[] = $id;
            }
        }

        return response()->json([
            'action' => $action,
            'processed' => $processed,
            'skipped' => $skipped,
        ]);
    }
}
