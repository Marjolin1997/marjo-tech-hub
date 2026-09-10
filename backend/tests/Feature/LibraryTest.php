<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['access_control.owner_email' => null]);
        $this->seed(AccessControlSeeder::class);
    }

    private function editor(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Editor');
        return $user;
    }

    public function test_user_can_create_nested_categories_and_entry(): void
    {
        $user = $this->editor(); Sanctum::actingAs($user);
        $parent = $this->postJson('/api/categories', ['name'=>'Laravel'])->assertCreated()->json('data');
        $child = $this->postJson('/api/categories', ['name'=>'Queues','parent_id'=>$parent['id']])->assertCreated()->json('data');
        $this->postJson('/api/entries', ['category_id'=>$child['id'],'title'=>'Restart queue workers','type'=>'command','language'=>'bash','content'=>'php artisan queue:restart'])
            ->assertCreated()->assertJsonPath('data.type','command')->assertJsonPath('data.category_id',$child['id']);
    }

    public function test_library_resources_are_private_to_their_owner(): void
    {
        $owner=$this->editor(); $other=$this->editor();
        $category=$owner->categories()->create(['name'=>'Private','slug'=>'private']);
        $entry=$owner->entries()->create(['category_id'=>$category->id,'title'=>'Private command','slug'=>'private-command','type'=>'command','content'=>'secret']);
        Sanctum::actingAs($other);
        $this->getJson("/api/entries/{$entry->id}")->assertNotFound();
        $this->putJson("/api/entries/{$entry->id}", ['title'=>'Changed','type'=>'command','content'=>'changed'])->assertNotFound();
        $this->deleteJson("/api/categories/{$category->id}")->assertNotFound();
    }

    public function test_category_cannot_be_moved_into_its_descendant(): void
    {
        $user=$this->editor();
        $parent=$user->categories()->create(['name'=>'Parent','slug'=>'parent']);
        $child=$user->categories()->create(['name'=>'Child','slug'=>'child','parent_id'=>$parent->id]); Sanctum::actingAs($user);
        $this->putJson("/api/categories/{$parent->id}", ['name'=>'Parent','parent_id'=>$child->id])->assertUnprocessable();
    }

    public function test_entry_search_is_scoped_to_current_user(): void
    {
        $user=$this->editor(); $other=$this->editor();
        $user->entries()->create(['title'=>'Docker logs','slug'=>'docker-logs','type'=>'command','content'=>'docker compose logs']);
        $other->entries()->create(['title'=>'Docker secret','slug'=>'docker-secret','type'=>'note','content'=>'hidden']); Sanctum::actingAs($user);
        $this->getJson('/api/entries?q=Docker')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.title','Docker logs');
    }
}
