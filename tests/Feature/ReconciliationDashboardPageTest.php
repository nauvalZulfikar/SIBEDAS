<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

/**
 * Pin the reconciliation dashboard view contract — clearance-driven UI hiding
 * is verified at the rendered HTML level, not just at the API level.
 *
 * NOTE: We use @runInSeparateProcess because the existing
 * `resources/views/layouts/partials/sidebar.blade.php` declares global
 * functions (isActiveMenu, renderMenu) inside an `@php` block. Rendering the
 * sidebar twice in one PHP process throws "Cannot redeclare". Each test
 * forks a fresh PHP process to bypass this pre-existing limitation.
 *
 * If these break, L1 users could see a Recompute button (server-side will
 * still 403, but UX would be confusing), or L2 users could lose access to
 * the audit tab.
 */
class ReconciliationDashboardPageTest extends TestCase
{
    public function test_unauthenticated_redirects_to_login(): void
    {
        $this->get('/dashboards/reconciliation')->assertRedirect('/login');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_l1_page_hides_recompute_and_audit_tab(): void
    {
        $u = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))->first();
        $this->actingAs($u);
        $r = $this->get('/dashboards/reconciliation')->assertOk();
        $r->assertDontSee('id="btn-recompute"', false);
        $r->assertDontSee('id="tab-audit-btn"', false);
        $r->assertSee('window.RECON_CLEARANCE = "level_1"', false);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_l2_page_shows_audit_tab_with_masked_badge(): void
    {
        $u = User::where('email', 'l2test@sibedas.local')->first();
        $this->actingAs($u);
        $r = $this->get('/dashboards/reconciliation')->assertOk();
        $r->assertSee('id="tab-audit-btn"', false);
        $r->assertSee('PII Masked', false);
        $r->assertDontSee('id="btn-recompute"', false);
        $r->assertSee('window.RECON_CLEARANCE = "level_2"', false);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_l3_page_shows_all_controls(): void
    {
        $u = User::whereHas('roles', fn ($q) => $q->where('name', 'superadmin'))->first();
        $this->actingAs($u);
        $r = $this->get('/dashboards/reconciliation')->assertOk();
        $r->assertSee('id="btn-recompute"', false);
        $r->assertSee('id="tab-audit-btn"', false);
        $r->assertSee('window.RECON_CLEARANCE = "level_3"', false);
    }
}
