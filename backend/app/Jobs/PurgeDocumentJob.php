<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PurgeDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $documentId)
    {
    }

    public function handle(): void
    {
        $document = Document::query()->withTrashed()->find($this->documentId);
        if (!$document) {
            return;
        }

        $document->versions()->each(function ($version): void {
            Storage::disk('private')->delete($version->path);
        });

        $document->forceDelete();
    }
}
