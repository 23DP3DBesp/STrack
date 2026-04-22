<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurring_costs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('car_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name'); // e.g. Insurance, Subscription
            $table->decimal('amount', 10, 2);

            $table->string('interval'); // monthly, yearly
            $table->date('next_due_date');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_costs');
    }
};