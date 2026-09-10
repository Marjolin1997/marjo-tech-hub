<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->string('direct_key', 80)->nullable()->after('type');
        });

        $directs = DB::table('conversations')->where('type', 'direct')->orderBy('id')->get(['id']);
        foreach ($directs as $conversation) {
            $ids = DB::table('conversation_participants')->where('conversation_id', $conversation->id)->orderBy('user_id')->pluck('user_id')->all();
            if (count($ids) !== 2) continue;
            $key = 'direct:'.$ids[0].':'.$ids[1];
            $existing = DB::table('conversations')->where('direct_key', $key)->first();
            if ($existing) {
                throw new RuntimeException("Duplicate direct conversation detected for {$key}; resolve it before applying this migration.");
            }
            DB::table('conversations')->where('id', $conversation->id)->update(['direct_key' => $key]);
        }

        Schema::table('conversations', function (Blueprint $table): void {
            $table->unique('direct_key', 'conversations_direct_key_unique');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['sender_id']);
            $table->unsignedBigInteger('sender_id')->nullable()->change();
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign(['sender_id']);
        });
        if (DB::table('messages')->whereNull('sender_id')->exists()) {
            throw new RuntimeException('Cannot restore non-null sender_id while messages with deleted senders exist.');
        }
        Schema::table('messages', function (Blueprint $table): void {
            $table->unsignedBigInteger('sender_id')->nullable(false)->change();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique('conversations_direct_key_unique');
            $table->dropColumn('direct_key');
        });
    }
};
