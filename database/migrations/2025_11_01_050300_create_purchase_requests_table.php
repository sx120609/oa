<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('no')->unique();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'submitted',
                'approved',
                'rejected',
                'ordered',
                'fulfilled',
            ])->default('draft');
            $table->string('title');
            $table->decimal('amount', 12, 2)->nullable();
            $table->date('requested_at')->nullable();
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
        Schema::dropIfExists('purchase_requests');
    }
};
