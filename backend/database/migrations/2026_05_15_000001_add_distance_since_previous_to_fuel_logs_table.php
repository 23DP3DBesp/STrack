<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fuel_logs', function (Blueprint $table): void {
            $table->unsignedInteger('distance_since_previous')->nullable()->after('mileage');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_logs', function (Blueprint $table): void {
            $table->dropColumn('distance_since_previous');
        });
    }
};
