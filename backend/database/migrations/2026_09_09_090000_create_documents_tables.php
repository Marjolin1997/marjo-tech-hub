<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('disk', 50)->default('documents');
            $table->string('path', 500);
            $table->string('mime_type', 120);
            $table->string('extension', 12);
            $table->unsignedBigInteger('size');
            $table->char('checksum', 64);
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'mime_type']);
            $table->index(['user_id', 'is_sensitive']);
        });

        Schema::create('document_tag', function (Blueprint $table): void {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['document_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_tag');
        Schema::dropIfExists('documents');
    }
};
