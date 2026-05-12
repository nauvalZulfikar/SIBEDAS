<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsurePbbClearance;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pin down the clearance rank resolution. A user with multiple roles takes
 * the HIGHEST clearance among those roles (rank-by-max). If this flips to
 * min/avg/most-recent, escalation to level_3 could become accidental.
 */
class PbbClearanceRankTest extends TestCase
{
    private function buildUser(array $rolesClearances): object
    {
        // Build a tiny mock that mirrors `$user->roles()->pluck('pbb_clearance')->all()`.
        return new class($rolesClearances) {
            public function __construct(private array $clearances) {}
            public function roles()
            {
                $clearances = $this->clearances;
                return new class($clearances) {
                    public function __construct(private array $clearances) {}
                    public function pluck(string $col)
                    {
                        return new class($this->clearances) {
                            public function __construct(private array $values) {}
                            public function all() { return $this->values; }
                        };
                    }
                };
            }
        };
    }

    private function resolve(object $user): string
    {
        $mw = new EnsurePbbClearance();
        $m = new ReflectionMethod($mw, 'resolveUserClearance');
        $m->setAccessible(true);
        return $m->invoke($mw, $user);
    }

    public function test_null_user_resolves_to_level_1(): void
    {
        $mw = new EnsurePbbClearance();
        $m = new ReflectionMethod($mw, 'resolveUserClearance');
        $m->setAccessible(true);
        $this->assertSame('level_1', $m->invoke($mw, null));
    }

    public function test_single_role_returns_that_clearance(): void
    {
        $this->assertSame('level_1', $this->resolve($this->buildUser(['level_1'])));
        $this->assertSame('level_2', $this->resolve($this->buildUser(['level_2'])));
        $this->assertSame('level_3', $this->resolve($this->buildUser(['level_3'])));
    }

    public function test_multi_role_takes_highest(): void
    {
        $this->assertSame('level_3', $this->resolve($this->buildUser(['level_1', 'level_3'])));
        $this->assertSame('level_3', $this->resolve($this->buildUser(['level_3', 'level_1'])));
        $this->assertSame('level_2', $this->resolve($this->buildUser(['level_1', 'level_2'])));
    }

    public function test_unknown_clearance_does_not_escalate(): void
    {
        // If a future migration adds 'level_99', we should not silently rank it
        // above level_3. Current impl treats unknown as rank 0 → won't override.
        $this->assertSame('level_3', $this->resolve($this->buildUser(['level_3', 'level_99'])));
        $this->assertSame('level_1', $this->resolve($this->buildUser(['level_99'])));
    }
}
