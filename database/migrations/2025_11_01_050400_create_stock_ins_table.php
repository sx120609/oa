<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->enum('status', [
                'pending',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->string('location')->nullable();
            $table->date('received_at')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('owned_by_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['owned_by_project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
