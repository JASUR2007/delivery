<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->string('contract_number')->unique();
            $table->string('supplier_name');
            $table->date('start_date');
            $table->unsignedInteger('duration_days');
            $table->date('deadline_date');
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('contracts');
    }
};
