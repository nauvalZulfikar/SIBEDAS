<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke tests for the public-facing auth surface.
 *
 * These don't touch Auth::attempt() because we don't want feature tests to
 * write to the production-shaped MySQL during local runs. They pin the
 * routing & rendering contract: unauthenticated users get redirected to
 * /login, the login page renders 200, and protected URLs redirect rather
 * than 500.
 */
class AuthRedirectTest extends TestCase
{
    public function test_root_redirects_to_login_when_unauthenticated(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('login', false);
    }

    public function test_protected_dashboard_redirects_to_login(): void
    {
        // Hitting a protected dashboard URL without a session should redirect,
        // not 500 (e.g. due to missing auth middleware).
        $response = $this->get('/dashboards/bigdata');
        $this->assertContains(
            $response->status(),
            [302, 403],
            'Protected URL should redirect or forbid, not error',
        );
    }

    public function test_login_post_with_empty_body_does_not_500(): void
    {
        // We don't assert a specific status — the validator may redirect-back
        // (302) or return 422. The contract is "no server error".
        $response = $this->post('/login', []);
        $this->assertLessThan(500, $response->status());
    }
}
