<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('latency_ms', 8, 2);
            $table->decimal('jitter_ms', 8, 2);
            $table->decimal('download_mbps', 10, 2);
            $table->decimal('upload_mbps', 10, 2);
            $table->string('connection_quality', 20);
            $table->string('effective_type', 20)->nullable();
            $table->decimal('reported_downlink_mbps', 10, 2)->nullable();
            $table->unsignedInteger('reported_rtt_ms')->nullable();
            $table->string('user_agent_family', 80)->nullable();
            $table->timestamp('tested_at');
            $table->timestamps();
            $table->index(['user_id', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_test_results');
    }
};
