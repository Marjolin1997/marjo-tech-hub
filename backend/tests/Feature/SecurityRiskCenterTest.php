<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRiskCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_inspection_requires_authentication(): void
    {
        $this->postJson('/api/security-center/inspect', ['target' => 'example.com'])
            ->assertUnauthorized();
    }

    public function test_security_inspection_rejects_non_http_protocols(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/security-center/inspect', ['target' => 'file:///etc/passwd'])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_security_inspection_rejects_localhost(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/security-center/inspect', ['target' => 'http://localhost'])
            ->assertStatus(422)
            ->assertJson(['message' => 'Local and private targets are not allowed.']);
    }

    public function test_security_inspection_validates_target_presence(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/security-center/inspect', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['target']);
    }
}
