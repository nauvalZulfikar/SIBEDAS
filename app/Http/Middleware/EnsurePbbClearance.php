<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsurePbbClearance
{
    private const LEVEL_RANK = [
        'level_1' => 1,
        'level_2' => 2,
        'level_3' => 3,
    ];

    public function handle(Request $request, Closure $next, string $required = 'level_1'): Response
    {
        $user = $request->user();
        $userClearance = $this->resolveUserClearance($user);
        $hasAccess = self::LEVEL_RANK[$userClearance] >= self::LEVEL_RANK[$required];

        // Audit log every level_2+ hit and every block. level_1 we skip to keep the
        // table small (kab/kec aggregate queries are very high volume).
        $shouldLog = !$hasAccess
            || self::LEVEL_RANK[$required] >= self::LEVEL_RANK['level_2'];

        if (!$hasAccess) {
            if ($shouldLog) $this->log($request, $user, $required, 403);
            return response()->json([
                'message' => "Akses ditolak. Endpoint ini butuh clearance {$required}; clearance Anda: {$userClearance}.",
                'clearance_required' => $required,
                'clearance_user' => $userClearance,
            ], 403);
        }

        $response = $next($request);
        if ($shouldLog) $this->log($request, $user, $required, $response->getStatusCode());
        return $response;
    }

    private function resolveUserClearance($user): string
    {
        if (!$user) return 'level_1';
        // Highest clearance among the user's roles wins.
        $levels = $user->roles()->pluck('pbb_clearance')->all();
        $best = 'level_1';
        foreach ($levels as $l) {
            if ((self::LEVEL_RANK[$l] ?? 0) > (self::LEVEL_RANK[$best] ?? 0)) $best = $l;
        }
        return $best;
    }

    private function log(Request $request, $user, string $required, int $status): void
    {
        try {
            DB::table('pbb_access_log')->insert([
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'clearance_required' => $required,
                'endpoint' => $request->route()?->getName() ?? $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'query_params' => json_encode($request->query()),
                'response_status' => $status,
                'accessed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Non-blocking: log failure should never break the API
        }
    }
}
