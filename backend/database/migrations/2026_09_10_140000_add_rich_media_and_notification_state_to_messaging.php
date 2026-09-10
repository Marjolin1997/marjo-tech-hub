<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->text('body')->nullable()->change();
        });

        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->foreignId('last_notified_message_id')->nullable()->after('last_read_message_id');
            $table->timestamp('last_notified_at')->nullable()->after('last_notified_message_id');
        });

        Schema::create('message_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('checksum_sha256', 64);
            $table->timestamps();
            $table->index(['message_id', 'id'], 'msg_attachment_message_idx');
        });

        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->foreign('last_notified_message_id', 'conv_participant_last_notified_fk')
                ->references('id')->on('messages')->nullOnDelete();
            $table->index(['user_id', 'last_notified_message_id'], 'conv_participant_notify_idx');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table): void {
            $table->dropForeign('conv_participant_last_notified_fk');
            $table->dropIndex('conv_participant_notify_idx');
            $table->dropColumn(['last_notified_message_id', 'last_notified_at']);
        });

        Schema::dropIfExists('message_attachments');

        Schema::table('messages', function (Blueprint $table): void {
            $table->text('body')->nullable(false)->change();
        });
    }
};
