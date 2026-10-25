<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('penalties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->date('calculation_date');
            $table->unsignedInteger('delay_days');
            $table->decimal('calculated_amount', 15, 2);
            $table->decimal('cap_amount', 15, 2);
            $table->string('status', 30)->default('pending');
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('breakdown')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('penalties');
    }
};
