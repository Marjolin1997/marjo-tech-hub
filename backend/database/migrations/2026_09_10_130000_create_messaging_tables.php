<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('direct');
            $table->timestamp('last_message_at')->nullable()->index('conv_last_message_idx');
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('last_read_message_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id'], 'conv_participant_unique');
            $table->index(['user_id', 'archived_at', 'conversation_id'], 'conv_participant_user_idx');
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id'], 'message_conversation_idx');
            $table->index(['sender_id', 'created_at'], 'message_sender_idx');
        });

        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->foreign('last_read_message_id', 'conv_participant_last_read_fk')
                ->references('id')->on('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropForeign('conv_participant_last_read_fk');
        });
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
