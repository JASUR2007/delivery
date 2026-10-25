<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('budget_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('financial_year');
            $table->string('cost_item');
            $table->unsignedTinyInteger('month');
            $table->decimal('allocated_amount', 15, 2);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['financial_year', 'cost_item', 'month'], 'budget_year_cost_month_unique');
        });
    }

    public function down(): void {
        Schema::dropIfExists('budget_items');
    }
};
