<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['owner_id', 'name']);
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('folder_id')->nullable()->after('owner_id')->constrained('folders')->nullOnDelete();
            $table->index(['folder_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['folder_id', 'is_archived']);
            $table->dropConstrainedForeignId('folder_id');
        });

        Schema::dropIfExists('folders');
    }
};
