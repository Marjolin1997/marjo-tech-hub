<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\SearchHistory;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UnifiedSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['access_control.owner_email' => null]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(AccessControlSeeder::class);
    }

    private function user(string $role = 'Viewer'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    public function test_search_requires_authentication_and_valid_query(): void
    {
        $this->getJson('/api/search?q=nginx')->assertUnauthorized();
        $user = $this->user();
        $this->actingAs($user)->getJson('/api/search?q=x')->assertUnprocessable();
        $this->actingAs($user)->getJson('/api/search?q='.str_repeat('a', 121))->assertUnprocessable();
    }

    public function test_library_search_is_owned_and_document_storage_metadata_never_leaks(): void
    {
        $alice = $this->user(); $bob = $this->user();
        $alice->entries()->create(['title'=>'Nginx Restart','slug'=>'nginx-restart','type'=>'command','content'=>'sudo systemctl restart nginx']);
        $bob->entries()->create(['title'=>'Nginx Secret','slug'=>'nginx-secret','type'=>'note','content'=>'private nginx content','is_sensitive'=>true]);
        Document::query()->create(['user_id'=>$alice->id,'title'=>'Nginx Guide','slug'=>'nginx-guide','original_name'=>'nginx.pdf','stored_name'=>'secret-storage-name.pdf','disk'=>'local','path'=>'private/secret/nginx.pdf','mime_type'=>'application/pdf','extension'=>'pdf','size'=>100,'checksum'=>'private-checksum']);

        $response = $this->actingAs($alice)->getJson('/api/search?q=nginx')->assertOk();
        $json = $response->json();
        $encoded = json_encode($json);
        $this->assertStringContainsString('Nginx Restart', $encoded);
        $this->assertStringContainsString('Nginx Guide', $encoded);
        $this->assertStringNotContainsString('Nginx Secret', $encoded);
        $this->assertStringNotContainsString('secret-storage-name.pdf', $encoded);
        $this->assertStringNotContainsString('private/secret', $encoded);
        $this->assertStringNotContainsString('private-checksum', $encoded);
    }

    public function test_message_search_is_participant_scoped_even_for_owner_and_admin(): void
    {
        $alice=$this->user(); $bob=$this->user(); $owner=$this->user('Owner'); $admin=$this->user('Admin');
        $id=$this->actingAs($alice)->postJson('/api/messaging/conversations',['recipient_id'=>$bob->id])->json('data.id');
        $this->actingAs($alice)->postJson("/api/messaging/conversations/{$id}/messages",['body'=>'Project zephyr private'])->assertCreated();

        $this->actingAs($bob)->getJson('/api/search?q=zephyr')->assertOk()->assertJsonPath('groups.0.type','messages');
        foreach ([$owner,$admin] as $outsider) {
            $response=$this->actingAs($outsider)->getJson('/api/search?q=zephyr')->assertOk();
            $this->assertSame('zephyr',$response->json('query'));
            $this->assertSame([], $response->json('groups'));
        }
    }

    public function test_like_wildcards_are_treated_as_literal_search_characters(): void
    {
        $user=$this->user();
        $user->entries()->create(['title'=>'Percent 100% safe','slug'=>'percent-safe','type'=>'note','content'=>'literal percent']);
        $user->entries()->create(['title'=>'Unrelated entry','slug'=>'unrelated','type'=>'note','content'=>'ordinary text']);
        $encoded=json_encode($this->actingAs($user)->getJson('/api/search?q=100%25')->assertOk()->json());
        $this->assertStringContainsString('Percent 100% safe',$encoded);
        $this->assertStringNotContainsString('Unrelated entry',$encoded);
    }

    public function test_recent_search_history_is_scoped_bounded_and_clearable(): void
    {
        $alice=$this->user(); $bob=$this->user();
        foreach (range(1,12) as $n) $this->actingAs($alice)->getJson('/api/search?q=query'.$n)->assertOk();
        $this->actingAs($bob)->getJson('/api/search?q=bob-only')->assertOk();

        $this->assertSame(10, SearchHistory::query()->where('user_id',$alice->id)->count());
        $this->actingAs($alice)->getJson('/api/search/history')->assertOk()->assertJsonCount(10,'data')->assertJsonMissing(['query'=>'bob-only']);
        $this->actingAs($alice)->deleteJson('/api/search/history')->assertOk()->assertJsonPath('cleared',true);
        $this->assertSame(0, SearchHistory::query()->where('user_id',$alice->id)->count());
        $this->assertSame(1, SearchHistory::query()->where('user_id',$bob->id)->count());
    }
}
