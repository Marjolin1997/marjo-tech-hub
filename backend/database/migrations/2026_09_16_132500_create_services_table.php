<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('type', 32)->default('service');
            $table->string('lifecycle', 32)->default('development');
            $table->text('description')->nullable();
            $table->string('repository_url', 2048)->nullable();
            $table->string('production_url', 2048)->nullable();
            $table->string('api_base_url', 2048)->nullable();
            $table->json('technologies')->nullable();
            $table->string('owner_label', 120)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'lifecycle']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};