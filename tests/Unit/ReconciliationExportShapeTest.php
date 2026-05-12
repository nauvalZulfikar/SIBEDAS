<?php

namespace Tests\Unit;

use App\Exports\ReconciliationExport;
use App\Services\PbbReconciliationService;
use PHPUnit\Framework\TestCase;

/**
 * Pin the shape of the multi-sheet export — order of sheets and titles matter
 * for downstream Bapenda templates that reference fixed sheet positions.
 */
class ReconciliationExportShapeTest extends TestCase
{
    public function test_export_has_4_sheets_in_order(): void
    {
        $svc = $this->createMock(PbbReconciliationService::class);
        $exp = new ReconciliationExport($svc, includePii: true);
        $sheets = $exp->sheets();

        $this->assertCount(4, $sheets);
        $this->assertSame('Summary Kab', $sheets[0]->title());
        $this->assertSame('Per Kecamatan', $sheets[1]->title());
        $this->assertSame('Per Kelurahan', $sheets[2]->title());
        $this->assertStringStartsWith('Audit', $sheets[3]->title());
    }

    public function test_audit_sheet_title_flags_pii_masked_when_disabled(): void
    {
        $svc = $this->createMock(PbbReconciliationService::class);

        $exp = new ReconciliationExport($svc, includePii: false);
        $this->assertStringContainsString('PII Masked', $exp->sheets()[3]->title());

        $exp2 = new ReconciliationExport($svc, includePii: true);
        $this->assertStringNotContainsString('PII Masked', $exp2->sheets()[3]->title());
    }
}
