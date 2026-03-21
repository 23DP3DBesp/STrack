<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->index(['owner_id', 'is_archived', 'is_starred', 'updated_at'], 'documents_owner_archive_star_updated_idx');
            $table->index(['owner_id', 'folder_id', 'updated_at'], 'documents_owner_folder_updated_idx');
            $table->index(['deleted_at'], 'documents_deleted_at_idx');
            $table->index(['size'], 'documents_size_idx');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex('documents_owner_archive_star_updated_idx');
            $table->dropIndex('documents_owner_folder_updated_idx');
            $table->dropIndex('documents_deleted_at_idx');
            $table->dropIndex('documents_size_idx');
        });
    }
};
