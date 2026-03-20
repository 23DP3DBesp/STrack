<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','developer','manager','user') NOT NULL DEFAULT 'user'");
            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('users_tmp_role_fix', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO users_tmp_role_fix (id, name, email, email_verified_at, password, role, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password, role, remember_token, created_at, updated_at
            FROM users
        ");

        Schema::drop('users');
        Schema::rename('users_tmp_role_fix', 'users');

        DB::statement("CREATE INDEX users_role_index ON users(role)");

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("UPDATE users SET role = 'user' WHERE role = 'developer'");
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','user') NOT NULL DEFAULT 'user'");
            return;
        }

        if ($driver !== 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::create('users_tmp_role_fix_down', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('user');
            $table->rememberToken();
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO users_tmp_role_fix_down (id, name, email, email_verified_at, password, role, remember_token, created_at, updated_at)
            SELECT id, name, email, email_verified_at, password,
                   CASE WHEN role = 'developer' THEN 'user' ELSE role END,
                   remember_token, created_at, updated_at
            FROM users
        ");

        Schema::drop('users');
        Schema::rename('users_tmp_role_fix_down', 'users');
        DB::statement("CREATE INDEX users_role_index ON users(role)");

        Schema::enableForeignKeyConstraints();
    }
};
