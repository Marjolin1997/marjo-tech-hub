<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_routes_require_authentication(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();
        $this->putJson('/api/profile', [])->assertUnauthorized();
        $this->getJson('/api/profile/avatar')->assertUnauthorized();
    }

    public function test_user_can_update_profile_without_exposing_avatar_storage_metadata(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/profile', [
            'first_name' => 'Marjolin',
            'last_name' => 'Jahja',
            'username' => 'marjolin.jahja',
            'job_title' => 'Full-Stack Engineer',
            'bio' => 'Laravel and React engineer.',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Marjolin Jahja')
            ->assertJsonPath('user.username', 'marjolin.jahja')
            ->assertJsonMissingPath('user.avatar_disk')
            ->assertJsonMissingPath('user.avatar_path');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Marjolin',
            'last_name' => 'Jahja',
            'username' => 'marjolin.jahja',
        ]);
    }

    public function test_username_must_be_unique(): void
    {
        User::factory()->create(['username' => 'existing-user']);
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/profile', [
            'first_name' => 'Another',
            'last_name' => 'User',
            'username' => 'existing-user',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');
    }

    public function test_user_can_upload_read_replace_and_delete_private_avatar(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/api/profile/avatar', [
            'avatar' => $this->fakePng('first.png'),
        ])->assertOk()->assertJsonPath('user.has_avatar', true);

        $firstPath = $user->fresh()->avatar_path;
        Storage::disk('local')->assertExists($firstPath);
        $this->get('/api/profile/avatar')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->post('/api/profile/avatar', [
            'avatar' => $this->fakePng('replacement.png'),
        ])->assertOk()->assertJsonPath('user.has_avatar', true);

        $secondPath = $user->fresh()->avatar_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);

        $this->deleteJson('/api/profile/avatar')->assertOk()->assertJsonPath('user.has_avatar', false);
        Storage::disk('local')->assertMissing($secondPath);
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_avatar_validation_rejects_unsupported_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('payload.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->avatar_path);
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
