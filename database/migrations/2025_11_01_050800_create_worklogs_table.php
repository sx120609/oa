<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worklogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained('repair_orders')->cascadeOnDelete();
            $table->string('no')->unique();
            $table->text('notes')->nullable();
            $table->dateTime('worked_at')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('owned_by_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['repair_order_id']);
            $table->index(['worked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worklogs');
    }
};
