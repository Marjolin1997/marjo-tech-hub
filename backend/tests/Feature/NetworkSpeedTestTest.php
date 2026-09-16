<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkSpeedTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_ping_and_bounded_download_are_available(): void
    {
        $this->get('/api/network-test/ping')->assertOk()->assertSeeText('ok');
        $this->get('/api/network-test/download?bytes=1000000')->assertOk()->assertHeader('Cache-Control');
    }

    public function test_oversized_upload_is_rejected(): void
    {
        $this->withHeader('Content-Length', '10000001')->post('/api/network-test/upload', [])->assertStatus(413);
    }

    public function test_authenticated_user_can_create_list_update_and_delete_own_result(): void
    {
        $user = User::factory()->create();
        $payload = ['latency_ms'=>20.4,'jitter_ms'=>3.2,'download_mbps'=>120.5,'upload_mbps'=>35.1,'connection_quality'=>'Excellent'];
        $created = $this->actingAs($user)->postJson('/api/network-test-results', $payload)->assertCreated()->json('result');
        $this->actingAs($user)->getJson('/api/network-test-results')->assertOk()->assertJsonPath('data.0.id', $created['id']);
        $this->actingAs($user)->putJson('/api/network-test-results/'.$created['id'], $payload + ['effective_type'=>'4g'])->assertOk();
        $this->actingAs($user)->deleteJson('/api/network-test-results/'.$created['id'])->assertOk();
        $this->assertDatabaseMissing('network_test_results', ['id'=>$created['id']]);
    }

    public function test_user_cannot_read_or_delete_another_users_result(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $result = $owner->networkTestResults()->create([
            'latency_ms'=>20,
            'jitter_ms'=>3,
            'download_mbps'=>50,
            'upload_mbps'=>10,
            'connection_quality'=>'Good',
            'tested_at'=>now(),
        ]);

        $this->actingAs($other)->getJson('/api/network-test-results/'.$result->id)->assertNotFound();
        $this->actingAs($other)->deleteJson('/api/network-test-results/'.$result->id)->assertNotFound();
        $this->assertDatabaseHas('network_test_results', ['id'=>$result->id, 'user_id'=>$owner->id]);
    }
}
