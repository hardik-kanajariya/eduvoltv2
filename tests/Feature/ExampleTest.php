<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test the health check endpoint.
     */
    public function test_health_check_endpoint(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'app' => 'EduVoltV2',
                'database' => [
                    'connection' => 'mysql',
                    'status' => 'connected'
                ]
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'app',
                'version',
                'database' => [
                    'connection',
                    'status'
                ]
            ]);
    }
}
