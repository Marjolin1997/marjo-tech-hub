<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('resource_type', 80);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->string('summary', 255);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['occurred_at', 'id']);
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['target_user_id', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index(['resource_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
