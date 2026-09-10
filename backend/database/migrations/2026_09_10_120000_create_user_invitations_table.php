<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->json('roles');
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['expires_at', 'accepted_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
