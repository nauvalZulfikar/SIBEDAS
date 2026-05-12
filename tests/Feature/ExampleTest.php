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
        // Unauthenticated visitors are redirected to /login.
        $response = $this->get('/');
        $response->assertRedirect('/login');

        // The login page itself must render.
        $this->get('/login')->assertStatus(200);
    }
}
