<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_user_can_upload_document_and_list_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $upload = UploadedFile::fake()->createWithContent('report.txt', 'hello world');

        $create = $this->postJson('/api/documents', [
            'title' => 'Report',
            'description' => 'Quarterly report',
            'file' => $upload,
        ]);

        $create->assertCreated()->assertJsonPath('title', 'Report');

        $list = $this->getJson('/api/documents');

        $list->assertOk();
        $this->assertCount(1, $list->json('data'));
    }

    public function test_owner_can_share_document_with_user(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        Sanctum::actingAs($owner);

        $upload = UploadedFile::fake()->createWithContent('share.txt', 'share me');
        $create = $this->postJson('/api/documents', [
            'title' => 'Shared document',
            'file' => $upload,
        ]);

        $documentId = (int) $create->json('id');

        $share = $this->postJson("/api/documents/{$documentId}/shares", [
            'user_id' => $collaborator->id,
            'permission' => 'edit',
        ]);

        $share->assertOk()->assertJsonPath('message', 'Access granted');
        $this->assertDatabaseHas('document_accesses', [
            'document_id' => $documentId,
            'user_id' => $collaborator->id,
            'permission' => 'edit',
        ]);
    }

    public function test_document_can_be_moved_to_trash_restored_and_purged(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/documents', [
            'title' => 'Disposable',
            'file' => UploadedFile::fake()->createWithContent('disposable.txt', 'temporary'),
        ]);

        $documentId = (int) $create->json('id');

        $delete = $this->deleteJson("/api/documents/{$documentId}");
        $delete->assertOk();

        $trash = $this->getJson('/api/documents/trash');
        $trash->assertOk();
        $this->assertCount(1, $trash->json('data'));

        $restore = $this->postJson("/api/documents/{$documentId}/restore");
        $restore->assertOk()->assertJsonPath('message', 'Document restored');

        $this->deleteJson("/api/documents/{$documentId}")->assertOk();
        $purge = $this->deleteJson("/api/documents/{$documentId}/purge");
        $purge->assertOk()->assertJsonPath('message', 'Document permanently deleted');

        $this->assertDatabaseMissing('documents', ['id' => $documentId]);
    }

    public function test_sensitive_route_is_rate_limited(): void
    {
        config()->set('docbox.security.sensitive_rate_per_minute', 1);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $create = $this->postJson('/api/documents', [
            'title' => 'Bulk target',
            'file' => UploadedFile::fake()->createWithContent('bulk.txt', 'bulk'),
        ]);
        $documentId = (int) $create->json('id');

        $payload = [
            'action' => 'trash',
            'document_ids' => [$documentId],
        ];

        $this->postJson('/api/documents/bulk', $payload)->assertOk();
        $this->postJson('/api/documents/bulk', $payload)->assertStatus(429);
    }

    public function test_non_owner_cannot_share_document(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($owner);
        $create = $this->postJson('/api/documents', [
            'title' => 'Private',
            'file' => UploadedFile::fake()->createWithContent('private.txt', 'private'),
        ]);
        $documentId = (int) $create->json('id');

        Sanctum::actingAs($other);
        $response = $this->postJson("/api/documents/{$documentId}/shares", [
            'user_id' => $target->id,
            'permission' => 'view',
        ]);

        $response->assertStatus(403);
    }
}
