<?php

namespace Tests\Unit;

use App\Models\BuildingType;
use App\Services\RetributionCalculatorService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

/**
 * Locks the math of RetributionCalculatorService::executeCalculation().
 *
 * The retribution formula is the project's most-tweaked area; these tests pin
 * down current behavior so future tweaks cause a visible test diff rather than
 * silent drift in production permit fees.
 *
 * Reference Excel formula (retained in the service):
 *   h5    = floor((coef * (ip_perm + ip_complex + (height_mult * height_idx))) * 10000) / 10000
 *   main  = area * locality_index * base_value * h5
 *   infra = infra_mult * main
 *   total = round(main + infra, 0)
 */
class RetributionCalculatorServiceTest extends TestCase
{
    private RetributionCalculatorService $service;
    private ReflectionMethod $execute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new RetributionCalculatorService();
        $this->execute = new ReflectionMethod($this->service, 'executeCalculation');
        $this->execute->setAccessible(true);
    }

    private function buildingType(): BuildingType
    {
        $bt = new BuildingType([
            'code' => 'TEST-A',
            'name' => 'Test Building',
            'is_free' => false,
            'is_active' => true,
        ]);
        $bt->id = 1;

        return $bt;
    }

    private function indices(array $overrides = []): stdClass
    {
        $idx = new stdClass();
        $idx->coefficient = 1.0;
        $idx->ip_permanent = 0.2;
        $idx->ip_complexity = 0.3;
        $idx->locality_index = 1.0;
        $idx->infrastructure_factor = 1.0;

        foreach ($overrides as $k => $v) {
            $idx->{$k} = $v;
        }

        return $idx;
    }

    private function call(
        $indices = null,
        float $heightIndex = 1.0,
        float $baseValue = 70350.0,
        float $infraMult = 0.5,
        float $heightMult = 0.5,
        int $floors = 1,
        float $area = 100.0,
        bool $excelMode = false,
    ): array {
        return $this->execute->invoke(
            $this->service,
            $this->buildingType(),
            $indices ?? $this->indices(),
            $heightIndex,
            $baseValue,
            $infraMult,
            $heightMult,
            $floors,
            $area,
            $excelMode,
        );
    }

    public function test_h5_coefficient_uses_rundown_to_4_decimals(): void
    {
        // coef=1, ip_perm=0.2, ip_complex=0.3, height_mult=0.5, height_idx=1.0
        // raw h5 = 1 * (0.2 + 0.3 + (0.5 * 1.0)) = 1.0 → already 4dp
        $r = $this->call();
        $this->assertSame(1.0, $r['calculation_steps']['h5_coefficient']['result']);

        // Force a non-clean fraction: coef=0.7, ip_perm=0.123, ip_complex=0.456, height_idx=0.789
        // raw = 0.7 * (0.123 + 0.456 + (0.5 * 0.789)) = 0.7 * 0.9735 = 0.68145
        // floor to 4dp = 0.6814
        $r = $this->call(
            indices: $this->indices(['coefficient' => 0.7, 'ip_permanent' => 0.123, 'ip_complexity' => 0.456]),
            heightIndex: 0.789,
        );
        $this->assertSame(0.6814, $r['calculation_steps']['h5_coefficient']['result']);
    }

    public function test_total_retribution_default_case(): void
    {
        // Inputs: defaults from indices() + base=70350, area=100, h5=1.0, locality=1.0
        // main  = 100 * 1.0 * 70350 * 1.0 = 7,035,000
        // infra = 0.5 * 7,035,000 = 3,517,500
        // total = round(10,552,500, 0) = 10,552,500
        $r = $this->call();
        $this->assertSame(7_035_000.0, $r['calculation_steps']['main_calculation']['result']);
        $this->assertSame(3_517_500.0, $r['calculation_steps']['infrastructure_calculation']['result']);
        $this->assertSame(10_552_500.0, $r['total_retribution']);
        $this->assertSame('Rp 10.552.500,00', $r['formatted_amount']);
    }

    public function test_total_scales_linearly_with_area(): void
    {
        $small = $this->call(area: 50.0)['total_retribution'];
        $large = $this->call(area: 200.0)['total_retribution'];

        // Doubling area from 50→100 should double total; 50→200 should 4x.
        $this->assertEqualsWithDelta($small * 4, $large, 1.0);
    }

    public function test_locality_index_multiplies_only_main_then_infra(): void
    {
        $base = $this->call()['total_retribution'];
        $hot  = $this->call(indices: $this->indices(['locality_index' => 2.0]))['total_retribution'];

        // Locality is a linear factor on `main`, infra is 0.5*main, so total scales linearly too.
        $this->assertSame($base * 2, $hot);
    }

    public function test_excel_compatible_mode_rounds_intermediates_first(): void
    {
        // Pick inputs where main + infra has fractional part that rounds differently
        // when summed as floats vs rounded individually.
        $idx = $this->indices(['locality_index' => 1.111, 'coefficient' => 0.7777]);

        $std = $this->call(indices: $idx, excelMode: false)['total_retribution'];
        $xl  = $this->call(indices: $idx, excelMode: true)['total_retribution'];

        // Both should be integers (rounded to 0dp).
        $this->assertSame(round($std, 0), $std);
        $this->assertSame(round($xl, 0),  $xl);

        // They might match or differ by ≤1 depending on the residual fraction;
        // the contract is just "≤1 IDR difference", which is the documented intent.
        $this->assertLessThanOrEqual(1.0, abs($std - $xl));
    }

    public function test_zero_area_yields_zero_total(): void
    {
        $r = $this->call(area: 0.0);
        $this->assertSame(0.0, $r['total_retribution']);
    }

    public function test_calculation_steps_structure_is_stable(): void
    {
        // Downstream code (UI breakdown, audit log) reads these exact keys —
        // pin the contract.
        $r = $this->call();

        $this->assertArrayHasKey('h5_coefficient', $r['calculation_steps']);
        $this->assertArrayHasKey('main_calculation', $r['calculation_steps']);
        $this->assertArrayHasKey('infrastructure_calculation', $r['calculation_steps']);
        $this->assertArrayHasKey('total_calculation', $r['calculation_steps']);

        $this->assertArrayHasKey('formula', $r['calculation_steps']['h5_coefficient']);
        $this->assertArrayHasKey('result', $r['calculation_steps']['h5_coefficient']);

        $this->assertArrayHasKey('h5', $r['calculation_detail']);
        $this->assertArrayHasKey('main', $r['calculation_detail']);
        $this->assertArrayHasKey('infrastructure', $r['calculation_detail']);
        $this->assertArrayHasKey('total', $r['calculation_detail']);
    }
}
