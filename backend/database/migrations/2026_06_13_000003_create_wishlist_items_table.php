<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 80);
            $table->decimal('estimated_price', 12, 2)->nullable();
            $table->string('store', 120)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('status', 40)->default('planned');
            $table->unsignedTinyInteger('priority')->default(2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
