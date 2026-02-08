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
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id')->nullable();
            $table->string('action'); // created, updated, deleted, etc.
            $table->string('model_type'); // App\Models\User, App\Models\Post, etc.
            $table->string('model_id'); // ID of the audited model
            $table->json('original_values')->nullable(); // Values before change
            $table->json('new_values')->nullable(); // Values after change
            $table->string('ip_address')->nullable(); // Client IP
            $table->string('user_agent')->nullable(); // Browser user agent
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
