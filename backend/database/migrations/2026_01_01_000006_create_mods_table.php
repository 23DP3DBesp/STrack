<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('cost', 10, 2);
            $table->date('date_installed');
            $table->string('performance_impact', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mods');
    }
};
