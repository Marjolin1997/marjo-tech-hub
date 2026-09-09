<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name', 80)->nullable()->after('name');
            $table->string('last_name', 80)->nullable()->after('first_name');
            $table->string('username', 60)->nullable()->unique()->after('last_name');
            $table->string('job_title', 120)->nullable()->after('username');
            $table->text('bio')->nullable()->after('job_title');
            $table->string('avatar_disk', 50)->nullable()->after('bio');
            $table->string('avatar_path', 500)->nullable()->after('avatar_disk');
        });

        DB::table('users')->orderBy('id')->get(['id', 'name'])->each(function ($user): void {
            $parts = preg_split('/\s+/', trim((string) $user->name), 2);
            DB::table('users')->where('id', $user->id)->update([
                'first_name' => $parts[0] ?: null,
                'last_name' => $parts[1] ?? null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['first_name', 'last_name', 'username', 'job_title', 'bio', 'avatar_disk', 'avatar_path']);
        });
    }
};
