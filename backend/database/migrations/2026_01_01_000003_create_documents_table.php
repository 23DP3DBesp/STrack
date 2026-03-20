<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('mime_type', 200);
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('current_version_id')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->index(['owner_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
