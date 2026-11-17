<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaspiStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_heartbeat_requires_a_token_and_expires_without_faking_an_online_pi(): void
    {
        $payload = ['mode' => 'register', 'camera' => 'ready', 'rfid' => 'error', 'pending_count' => 7];
        $this->postJson(route('api.raspi.heartbeat'), $payload)->assertUnauthorized();
        $this->getJson(route('raspi.status'))->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson(route('raspi.status'))->assertOk()->assertJsonPath('online', false)->assertJsonPath('data', null);
        $this->postJson(route('api.raspi.heartbeat'), $payload, ['X-API-Token' => 'test-rfid-token'])->assertOk();
        $this->getJson(route('raspi.status'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('online', true)->assertJsonPath('data.mode', 'register')
            ->assertJsonPath('data.rfid', 'error')->assertJsonPath('data.pending_count', 7);
        $this->travel(36)->seconds();
        $this->getJson(route('raspi.status'))->assertOk()->assertJsonPath('online', false)->assertJsonPath('data.pending_count', 7);
        $this->assertDatabaseCount('assets', 0);
        $this->travel(25)->hours();
        $this->getJson(route('raspi.status'))->assertOk()->assertJsonPath('data', null);
    }

    public function test_heartbeat_validates_hardware_states_and_allows_an_unknown_queue_count(): void
    {
        $headers = ['X-API-Token' => 'test-rfid-token'];
        $this->postJson(route('api.raspi.heartbeat'), ['mode' => 'fake', 'camera' => 'ready', 'rfid' => 'ready', 'pending_count' => -1], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors(['mode', 'pending_count']);
        $this->postJson(route('api.raspi.heartbeat'), ['mode' => 'check', 'camera' => 'disabled', 'rfid' => 'ready', 'pending_count' => null], $headers)->assertOk();
        $this->actingAs(User::factory()->create())->getJson(route('raspi.status'))->assertOk()
            ->assertJsonPath('data.camera', 'disabled')->assertJsonPath('data.pending_count', null);
    }
}
