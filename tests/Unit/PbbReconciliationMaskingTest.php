<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ReconciliationController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pin down the PII masking pattern used to satisfy UU PDP for level_2 callers.
 *
 * Pattern: keep first 2 chars + asterisks + keep last 1 char. Strings shorter
 * than 5 chars become full asterisks (no useful info to retain).
 *
 * If this breaks, audit-tab data could leak names that should be masked, OR
 * masked output could become un-deobscured (defeating the masking).
 */
class PbbReconciliationMaskingTest extends TestCase
{
    private ReflectionMethod $mask;

    protected function setUp(): void
    {
        parent::setUp();
        $ref = new \ReflectionClass(ReconciliationController::class);
        // The controller has a constructor dep, so we instantiate without it
        // and call the private method via reflection — pure string transformation.
        $this->mask = $ref->getMethod('maskString');
        $this->mask->setAccessible(true);
    }

    private function instance(): ReconciliationController
    {
        // We never call non-static methods that need the service, so we
        // instantiate with a stub.
        return (new \ReflectionClass(ReconciliationController::class))
            ->newInstanceWithoutConstructor();
    }

    public function test_short_strings_become_full_asterisks(): void
    {
        $c = $this->instance();
        $this->assertSame('', $this->mask->invoke($c, ''));
        $this->assertSame('*', $this->mask->invoke($c, 'A'));
        $this->assertSame('**', $this->mask->invoke($c, 'AB'));
        $this->assertSame('***', $this->mask->invoke($c, 'ABC'));
        $this->assertSame('****', $this->mask->invoke($c, 'ABCD'));
    }

    public function test_typical_name_is_masked_first2_dots_last1(): void
    {
        $c = $this->instance();
        $this->assertSame('AD**********H', $this->mask->invoke($c, 'ADAH JUBAEDAH'));
        // Length 4 (e.g. "BUDI") is below the keep-2/keep-1 threshold → full asterisks
        $this->assertSame('****', $this->mask->invoke($c, 'BUDI'));
        // Length 5+ keeps first 2 + last 1 with at least 3 asterisks
        $this->assertSame('SI*****O', $this->mask->invoke($c, 'SISWANTO'));
        $this->assertSame('AB***E', $this->mask->invoke($c, 'ABCDE'));
    }

    public function test_address_with_special_chars_is_masked(): void
    {
        $c = $this->instance();
        $masked = $this->mask->invoke($c, 'KP CIPASIR RT:01 RW:02');
        $this->assertStringStartsWith('KP', $masked);
        $this->assertStringEndsWith('2', $masked);
        $this->assertMatchesRegularExpression('/^KP\*+2$/', $masked);
    }

    public function test_unicode_safe(): void
    {
        $c = $this->instance();
        // Multibyte counts must use mb_strlen — non-ASCII names should not be truncated
        $masked = $this->mask->invoke($c, 'BUDÍ HARTÖNŌ');
        $this->assertStringStartsWith('BU', $masked);
        $this->assertStringEndsWith('Ō', $masked);
    }

    public function test_whitespace_is_trimmed_before_mask(): void
    {
        $c = $this->instance();
        $this->assertSame('AD**********H', $this->mask->invoke($c, '  ADAH JUBAEDAH  '));
    }
}
