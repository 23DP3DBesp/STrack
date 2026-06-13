<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table): void {
            $table->decimal('purchase_price', 12, 2)->nullable()->after('license_plate');
            $table->date('purchase_date')->nullable()->after('purchase_price');
            $table->decimal('current_value', 12, 2)->nullable()->after('purchase_date');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table): void {
            $table->dropColumn(['purchase_price', 'purchase_date', 'current_value']);
        });
    }
};
