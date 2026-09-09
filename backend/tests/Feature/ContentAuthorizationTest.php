<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Entry;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['access_control.owner_email' => null]);
        $this->seed(AccessControlSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_user_without_permissions_is_forbidden_from_content_api(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/entries')->assertForbidden();
        $this->actingAs($user)->getJson('/api/categories')->assertForbidden();
        $this->actingAs($user)->getJson('/api/tags')->assertForbidden();
        $this->actingAs($user)->getJson('/api/documents')->assertForbidden();
    }

    public function test_viewer_can_read_owned_content_but_cannot_write(): void
    {
        $viewer = $this->userWithRole('Viewer');
        $entry = $viewer->entries()->create(['title'=>'Read me','slug'=>'read-me','type'=>'note','content'=>'content']);
        $this->actingAs($viewer)->getJson('/api/entries')->assertOk();
        $this->actingAs($viewer)->getJson('/api/entries/'.$entry->id)->assertOk();
        $this->actingAs($viewer)->postJson('/api/entries',['title'=>'Denied','type'=>'note','content'=>'x'])->assertForbidden();
        $this->actingAs($viewer)->deleteJson('/api/entries/'.$entry->id)->assertForbidden();
    }

    public function test_editor_can_manage_owned_content(): void
    {
        $editor = $this->userWithRole('Editor');
        $response = $this->actingAs($editor)->postJson('/api/entries',['title'=>'Editable','type'=>'note','content'=>'first'])->assertCreated();
        $entryId = $response->json('data.id');
        $this->actingAs($editor)->putJson('/api/entries/'.$entryId,['title'=>'Updated','type'=>'note','content'=>'second'])->assertOk();
        $this->actingAs($editor)->deleteJson('/api/entries/'.$entryId)->assertNoContent();
    }

    public function test_permissions_do_not_bypass_cross_user_ownership(): void
    {
        $owner = $this->userWithRole('Owner');
        $other = User::factory()->create();
        $entry = $other->entries()->create(['title'=>'Private','slug'=>'private','type'=>'note','content'=>'secret']);
        $this->actingAs($owner)->getJson('/api/entries/'.$entry->id)->assertNotFound();
        $this->actingAs($owner)->deleteJson('/api/entries/'.$entry->id)->assertNotFound();
    }

    public function test_viewer_cannot_create_categories_or_tags(): void
    {
        $viewer = $this->userWithRole('Viewer');
        $this->actingAs($viewer)->postJson('/api/categories',['name'=>'Denied'])->assertForbidden();
        $this->actingAs($viewer)->postJson('/api/tags',['name'=>'Denied'])->assertForbidden();
    }
}
