<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table): void {
            $table->date('insurance_until')->nullable()->after('license_plate');
            $table->date('inspection_until')->nullable()->after('insurance_until');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table): void {
            $table->dropColumn(['insurance_until', 'inspection_until']);
        });
    }
};
