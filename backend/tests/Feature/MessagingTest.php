<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MessagingTest extends TestCase
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

    public function test_guest_cannot_access_messaging(): void
    {
        $this->getJson('/api/messaging/conversations')->assertUnauthorized();
        $this->postJson('/api/messaging/conversations', ['recipient_id' => 1])->assertUnauthorized();
    }

    public function test_participant_can_create_reuse_send_read_and_archive_direct_conversation(): void
    {
        $alice = $this->user(); $bob = $this->user('Editor');
        $created = $this->actingAs($alice)->postJson('/api/messaging/conversations', ['recipient_id' => $bob->id])->assertCreated();
        $id = $created->json('data.id');
        $this->actingAs($alice)->postJson('/api/messaging/conversations', ['recipient_id' => $bob->id])->assertCreated()->assertJsonPath('data.id', $id);
        $message = $this->actingAs($alice)->postJson("/api/messaging/conversations/{$id}/messages", ['body' => 'Hello Bob'])->assertCreated();
        $messageId = $message->json('data.id');
        $this->actingAs($bob)->getJson('/api/messaging/conversations')->assertOk()->assertJsonPath('data.0.unread_count', 1);
        $this->actingAs($bob)->getJson("/api/messaging/conversations/{$id}/messages")->assertOk()->assertJsonPath('data.0.body', 'Hello Bob');
        $this->actingAs($bob)->putJson("/api/messaging/conversations/{$id}/read", ['message_id' => $messageId])->assertOk()->assertJsonPath('last_read_message_id', $messageId);
        $this->actingAs($bob)->putJson("/api/messaging/conversations/{$id}/archive")->assertOk()->assertJsonPath('archived', true);
        $this->actingAs($bob)->getJson('/api/messaging/conversations')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_non_participant_is_hidden_even_when_admin_or_owner(): void
    {
        $alice = $this->user(); $bob = $this->user(); $admin = $this->user('Admin'); $owner = $this->user('Owner');
        $id = $this->actingAs($alice)->postJson('/api/messaging/conversations', ['recipient_id' => $bob->id])->json('data.id');
        $this->actingAs($alice)->postJson("/api/messaging/conversations/{$id}/messages", ['body' => 'Private'])->assertCreated();
        foreach ([$admin, $owner] as $outsider) {
            $this->actingAs($outsider)->getJson("/api/messaging/conversations/{$id}/messages")->assertNotFound();
            $this->actingAs($outsider)->postJson("/api/messaging/conversations/{$id}/messages", ['body' => 'Nope'])->assertNotFound();
            $this->actingAs($outsider)->putJson("/api/messaging/conversations/{$id}/archive")->assertNotFound();
        }
    }

    public function test_user_without_messaging_permissions_is_forbidden(): void
    {
        $user = User::factory()->create(); $recipient = $this->user();
        $this->actingAs($user)->getJson('/api/messaging/conversations')->assertForbidden();
        $this->actingAs($user)->postJson('/api/messaging/conversations', ['recipient_id' => $recipient->id])->assertForbidden();
    }

    public function test_invalid_self_and_empty_messages_are_rejected(): void
    {
        $alice = $this->user(); $bob = $this->user();
        $this->actingAs($alice)->postJson('/api/messaging/conversations', ['recipient_id' => $alice->id])->assertUnprocessable();
        $id = $this->actingAs($alice)->postJson('/api/messaging/conversations', ['recipient_id' => $bob->id])->json('data.id');
        $this->actingAs($alice)->postJson("/api/messaging/conversations/{$id}/messages", ['body' => '   '])->assertUnprocessable();
        $this->assertSame(0, Message::query()->count());
    }

    public function test_message_history_uses_stable_descending_cursor_pagination(): void
    {
        $alice = $this->user(); $bob = $this->user();
        $id = $this->actingAs($alice)->postJson('/api/messaging/conversations', ['recipient_id' => $bob->id])->json('data.id');
        $conversation = Conversation::query()->findOrFail($id);
        foreach (range(1, 35) as $number) {
            $conversation->messages()->create(['sender_id' => $alice->id, 'body' => "Message {$number}"]);
        }
        $response = $this->actingAs($bob)->getJson("/api/messaging/conversations/{$id}/messages")->assertOk()->assertJsonCount(30, 'data');
        $this->assertNotNull($response->json('next_cursor'));
        $this->assertSame('Message 35', $response->json('data.0.body'));
        $this->assertSame('Message 6', $response->json('data.29.body'));
    }
}
