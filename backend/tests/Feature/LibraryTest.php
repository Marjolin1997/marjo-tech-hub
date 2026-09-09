<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Entry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_nested_categories_and_entry(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $parent = $this->postJson('/api/categories', ['name' => 'Laravel'])
            ->assertCreated()->json('data');

        $child = $this->postJson('/api/categories', ['name' => 'Queues', 'parent_id' => $parent['id']])
            ->assertCreated()->json('data');

        $this->postJson('/api/entries', [
            'category_id' => $child['id'],
            'title' => 'Restart queue workers',
            'type' => 'command',
            'language' => 'bash',
            'content' => 'php artisan queue:restart',
        ])->assertCreated()
          ->assertJsonPath('data.type', 'command')
          ->assertJsonPath('data.category_id', $child['id']);
    }

    public function test_library_resources_are_private_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $category = $owner->categories()->create(['name' => 'Private', 'slug' => 'private']);
        $entry = $owner->entries()->create([
            'category_id' => $category->id,
            'title' => 'Private command', 'slug' => 'private-command', 'type' => 'command', 'content' => 'secret',
        ]);

        Sanctum::actingAs($other);

        $this->getJson("/api/entries/{$entry->id}")->assertNotFound();
        $this->putJson("/api/entries/{$entry->id}", [
            'title' => 'Changed', 'type' => 'command', 'content' => 'changed',
        ])->assertNotFound();
        $this->deleteJson("/api/categories/{$category->id}")->assertNotFound();
    }

    public function test_category_cannot_be_moved_into_its_descendant(): void
    {
        $user = User::factory()->create();
        $parent = $user->categories()->create(['name' => 'Parent', 'slug' => 'parent']);
        $child = $user->categories()->create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/categories/{$parent->id}", [
            'name' => 'Parent',
            'parent_id' => $child->id,
        ])->assertUnprocessable();
    }

    public function test_entry_search_is_scoped_to_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->entries()->create(['title' => 'Docker logs', 'slug' => 'docker-logs', 'type' => 'command', 'content' => 'docker compose logs']);
        $other->entries()->create(['title' => 'Docker secret', 'slug' => 'docker-secret', 'type' => 'note', 'content' => 'hidden']);
        Sanctum::actingAs($user);

        $this->getJson('/api/entries?q=Docker')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Docker logs');
    }
}
