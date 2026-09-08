<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryProductivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_tag_and_favorite_an_entry_then_filter_it(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $tag = $this->postJson('/api/tags', ['name' => 'Laravel'])
            ->assertCreated()->json('data');

        $entry = $this->postJson('/api/entries', [
            'title' => 'Restart queue workers',
            'type' => 'command',
            'language' => 'bash',
            'content' => 'php artisan queue:restart',
            'tag_ids' => [$tag['id']],
        ])->assertCreated()
          ->assertJsonPath('data.tags.0.id', $tag['id'])
          ->assertJsonPath('data.is_favorite', false)
          ->json('data');

        $this->putJson("/api/entries/{$entry['id']}/favorite")
            ->assertOk()
            ->assertJsonPath('is_favorite', true);

        $this->getJson('/api/entries?favorite=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $entry['id'])
            ->assertJsonPath('data.0.is_favorite', true);

        $this->getJson("/api/entries?tag_id={$tag['id']}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $entry['id']);
    }

    public function test_favorite_endpoint_is_idempotent(): void
    {
        $user = User::factory()->create();
        $entry = $user->entries()->create(['title' => 'Logs', 'slug' => 'logs', 'type' => 'command', 'content' => 'tail -f app.log']);
        Sanctum::actingAs($user);

        $this->putJson("/api/entries/{$entry->id}/favorite")->assertOk();
        $this->putJson("/api/entries/{$entry->id}/favorite")->assertOk();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_user_cannot_assign_another_users_tag(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $foreignTag = $other->tags()->create(['name' => 'Private', 'slug' => 'private']);
        Sanctum::actingAs($owner);

        $this->postJson('/api/entries', [
            'title' => 'Unsafe assignment',
            'type' => 'note',
            'content' => 'must be rejected',
            'tag_ids' => [$foreignTag->id],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('entry_tag', ['tag_id' => $foreignTag->id]);
    }

    public function test_user_cannot_favorite_or_delete_another_users_resources(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $entry = $owner->entries()->create(['title' => 'Owner entry', 'slug' => 'owner-entry', 'type' => 'note', 'content' => 'private']);
        $tag = $owner->tags()->create(['name' => 'Owner tag', 'slug' => 'owner-tag']);
        Sanctum::actingAs($other);

        $this->putJson("/api/entries/{$entry->id}/favorite")->assertNotFound();
        $this->deleteJson("/api/entries/{$entry->id}/favorite")->assertNotFound();
        $this->deleteJson("/api/tags/{$tag->id}")->assertNotFound();
    }

    public function test_duplicate_tag_names_normalize_to_one_user_owned_tag(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/tags', ['name' => 'CI CD'])->assertCreated()->json('data.id');
        $second = $this->postJson('/api/tags', ['name' => 'CI-CD'])->assertOk()->json('data.id');

        $this->assertSame($first, $second);
        $this->assertDatabaseCount('tags', 1);
    }
}
