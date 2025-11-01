<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('specification')->nullable();
            $table->string('status')->default('purchased');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('inbounded_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('repaired_at')->nullable();
            $table->timestamp('scrapped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
