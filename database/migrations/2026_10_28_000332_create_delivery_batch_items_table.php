<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('delivery_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_batch_id')->constrained('delivery_batches')->cascadeOnDelete();
            $table->foreignId('contract_item_id')->constrained('contract_items')->cascadeOnDelete();
            $table->unsignedInteger('delivered_paid_quantity')->default(0);
            $table->unsignedInteger('delivered_bonus_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('delivery_batch_items');
    }
};
