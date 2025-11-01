<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_orders', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'created',
                'assigned',
                'diagnosed',
                'waiting_parts',
                'repairing',
                'qa',
                'closed',
                'scrapped',
            ])->default('created');
            $table->dateTime('reported_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->text('summary')->nullable();
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
        Schema::dropIfExists('repair_orders');
    }
};
