<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('display_name')->nullable()->after('login');
            $table->string('locale', 8)->default('en')->after('display_name');
            $table->string('theme', 16)->default('light')->after('locale');
            $table->string('currency', 8)->default('EUR')->after('theme');
            $table->string('distance_unit', 8)->default('km')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'locale', 'theme', 'currency', 'distance_unit']);
        });
    }
};
