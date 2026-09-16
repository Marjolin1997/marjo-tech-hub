<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Payments API',
            'type' => 'api',
            'lifecycle' => 'production',
            'description' => 'Handles payment orchestration.',
            'repository_url' => 'https://github.com/example/payments-api',
            'production_url' => 'https://payments.example.com',
            'api_base_url' => 'https://payments.example.com/api',
            'technologies' => ['Laravel', 'MySQL'],
            'owner_label' => 'Platform Team',
        ], $overrides);
    }

    public function test_editor_can_create_read_update_and_delete_own_service(): void
    {
        $user = $this->editor(); Sanctum::actingAs($user);
        $service = $this->postJson('/api/services', $this->payload())->assertCreated()
            ->assertJsonPath('data.name', 'Payments API')->assertJsonPath('data.slug', 'payments-api')->json('data');
        $this->getJson('/api/services/'.$service['id'])->assertOk()->assertJsonPath('data.type', 'api');
        $this->putJson('/api/services/'.$service['id'], $this->payload(['name'=>'Payments Platform','lifecycle'=>'maintenance']))
            ->assertOk()->assertJsonPath('data.slug','payments-platform')->assertJsonPath('data.lifecycle','maintenance');
        $this->deleteJson('/api/services/'.$service['id'])->assertNoContent();
        $this->assertDatabaseMissing('services', ['id'=>$service['id']]);
    }

    public function test_services_are_private_to_their_owner(): void
    {
        $owner=$this->editor(); $other=$this->editor();
        $service=$owner->services()->create($this->payload(['slug'=>'private-api']));
        Sanctum::actingAs($other);
        $this->getJson('/api/services/'.$service->id)->assertNotFound();
        $this->putJson('/api/services/'.$service->id, $this->payload())->assertNotFound();
        $this->deleteJson('/api/services/'.$service->id)->assertNotFound();
    }

    public function test_catalog_search_and_filters_are_owner_scoped(): void
    {
        $user=$this->editor(); $other=$this->editor();
        $user->services()->create($this->payload(['name'=>'Central API','slug'=>'central-api','technologies'=>['Laravel','Redis']]));
        $user->services()->create($this->payload(['name'=>'Web Portal','slug'=>'web-portal','type'=>'website','lifecycle'=>'development','technologies'=>['React']]));
        $other->services()->create($this->payload(['name'=>'Hidden Central','slug'=>'hidden-central']));
        Sanctum::actingAs($user);
        $this->getJson('/api/services?q=Central&type=api&lifecycle=production&technology=Redis')
            ->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.name','Central API');
    }

    public function test_catalog_rejects_invalid_urls_and_enums(): void
    {
        Sanctum::actingAs($this->editor());
        $this->postJson('/api/services', $this->payload(['type'=>'shell','production_url'=>'file:///etc/passwd']))
            ->assertUnprocessable()->assertJsonValidationErrors(['type','production_url']);
    }

    public function test_duplicate_names_receive_owner_scoped_unique_slugs(): void
    {
        Sanctum::actingAs($this->editor());
        $first=$this->postJson('/api/services',$this->payload())->assertCreated()->json('data.slug');
        $second=$this->postJson('/api/services',$this->payload())->assertCreated()->json('data.slug');
        $this->assertSame('payments-api',$first); $this->assertSame('payments-api-2',$second);
    }
}
