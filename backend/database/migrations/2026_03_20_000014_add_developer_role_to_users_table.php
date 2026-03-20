<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','developer','manager','user') NOT NULL DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE users SET role = 'user' WHERE role = 'developer'");
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','user') NOT NULL DEFAULT 'user'");
        }
    }
};
