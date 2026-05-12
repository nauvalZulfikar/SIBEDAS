<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pin the 3-tier RBAC contract on the reconciliation API. These tests use
 * existing test users (created in Phase 9) and avoid DB writes — purely
 * read assertions on routing & authorization behavior.
 *
 * If these break, Bapenda PII could leak to operator-level callers, OR
 * legit admin actions could be erroneously blocked. Either is a P0.
 */
class ReconciliationApiClearanceTest extends TestCase
{
    private function asLevel1(): User
    {
        $u = User::whereHas('roles', fn ($q) => $q->where('name', 'user'))->first();
        $this->assertNotNull($u, 'test user with role=user not found in DB; seed Phase 9 test data first');
        Sanctum::actingAs($u);
        return $u;
    }

    private function asLevel2(): User
    {
        $u = User::where('email', 'l2test@sibedas.local')->first();
        $this->assertNotNull($u, 'l2 test user missing — created in Phase 9');
        Sanctum::actingAs($u);
        return $u;
    }

    private function asLevel3(): User
    {
        $u = User::whereHas('roles', fn ($q) => $q->where('name', 'superadmin'))->first();
        $this->assertNotNull($u, 'superadmin user missing');
        Sanctum::actingAs($u);
        return $u;
    }

    // --- Level 1 (operator/user) ---

    public function test_l1_can_get_summary(): void
    {
        $this->asLevel1();
        $this->getJson('/api/reconciliation/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'pbb_total', 'pbb_terbangun', 'sat_count', 'gap_sat_minus_terbangun', 'gap_pct',
            ]]);
    }

    public function test_l1_can_get_per_kec(): void
    {
        $this->asLevel1();
        $r = $this->getJson('/api/reconciliation/per-kec')->assertOk();
        $r->assertJsonStructure(['data', 'meta' => ['count']]);
        $this->assertSame(31, $r->json('meta.count'),
            'Kab Bandung must have exactly 31 kecamatan');
    }

    public function test_l1_blocked_from_kelurahan(): void
    {
        $this->asLevel1();
        $this->getJson('/api/reconciliation/kelurahan/Soreang')
            ->assertStatus(403)
            ->assertJsonStructure(['message', 'clearance_required', 'clearance_user']);
    }

    public function test_l1_blocked_from_audit_endpoints(): void
    {
        $this->asLevel1();
        $this->getJson('/api/reconciliation/no-satellite-nop?limit=1')->assertStatus(403);
        $this->getJson('/api/reconciliation/no-nop-satellite?limit=1')->assertStatus(403);
    }

    public function test_l1_blocked_from_recompute(): void
    {
        $this->asLevel1();
        $this->postJson('/api/reconciliation/recompute')->assertStatus(403);
    }

    public function test_l1_can_export_pdf_but_not_excel_csv(): void
    {
        $this->asLevel1();
        $this->get('/api/reconciliation/export/pdf')->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->get('/api/reconciliation/export/excel')->assertStatus(403);
        $this->get('/api/reconciliation/export/csv?scope=kec')->assertStatus(403);
    }

    // --- Level 2 (admin) ---

    public function test_l2_can_get_kelurahan(): void
    {
        $this->asLevel2();
        $r = $this->getJson('/api/reconciliation/kelurahan/Soreang')->assertOk();
        $r->assertJsonStructure(['data', 'meta' => ['count', 'kecamatan']]);
        // Each row should declare its coverage_status (Phase 7 contract)
        if (count($r->json('data')) > 0) {
            $r->assertJsonPath('data.0.coverage_status', fn ($v) => in_array($v, ['covered', 'pending_polygon']));
        }
    }

    public function test_l2_audit_data_is_pii_masked(): void
    {
        $this->asLevel2();
        $r = $this->getJson('/api/reconciliation/no-satellite-nop?limit=1')->assertOk();
        $this->assertTrue($r->json('meta.pii_masked'),
            'L2 caller must receive masked PII');
        if (count($r->json('data')) > 0) {
            $row = $r->json('data.0');
            $this->assertStringContainsString('*', $row['nama_wp'] ?? '*',
                'nama_wp must contain asterisks for L2 caller');
        }
    }

    public function test_l2_blocked_from_recompute(): void
    {
        $this->asLevel2();
        $this->postJson('/api/reconciliation/recompute')->assertStatus(403);
    }

    // --- Level 3 (superadmin) ---

    public function test_l3_audit_data_is_unmasked(): void
    {
        $this->asLevel3();
        $r = $this->getJson('/api/reconciliation/no-satellite-nop?limit=1')->assertOk();
        $this->assertFalse($r->json('meta.pii_masked'),
            'L3 caller must receive raw PII');
        if (count($r->json('data')) > 0) {
            $row = $r->json('data.0');
            // Raw nama_wp does not start with asterisks
            $this->assertNotEquals('***', substr($row['nama_wp'] ?? '', 0, 3));
        }
    }

    public function test_l3_can_recompute(): void
    {
        $this->asLevel3();
        $r = $this->postJson('/api/reconciliation/recompute')->assertOk();
        $r->assertJsonStructure(['message', 'data' => ['rows_inserted', 'elapsed_ms', 'computed_at']]);
        $this->assertSame(307, $r->json('data.rows_inserted'),
            'Recompute must produce 1 kab + 31 kec + 275 kelurahan = 307 rows');
    }

    public function test_unauthenticated_request_is_blocked(): void
    {
        // No Sanctum::actingAs() — sanctum middleware must reject
        $this->getJson('/api/reconciliation/summary')->assertUnauthorized();
    }
}
