<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->string('name');
            $table->string('asset_tag')->nullable()->unique();
            $table->enum('status', [
                'draft',
                'purchased',
                'in_stock',
                'in_use',
                'under_repair',
                'disposed',
            ])->default('draft');
            $table->string('category')->nullable();
            $table->string('serial_number')->nullable();
            $table->json('specification')->nullable();
            $table->json('metadata')->nullable();
            $table->date('purchased_at')->nullable();
            $table->foreignId('current_user_id')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('assets');
    }
};
