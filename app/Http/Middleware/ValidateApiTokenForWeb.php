<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiTokenForWeb
{
    /**
     * Handle an incoming request.
     * Middleware ini memvalidasi token API untuk web requests
     * dan melakukan auto-logout jika token tidak valid
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation untuk non-authenticated routes
        if (!Auth::check()) {
            return $next($request);
        }

        // Skip validation untuk API routes (sudah ditangani oleh auth:sanctum)
        if ($request->is('api/*')) {
            return $next($request);
        }

        $user = Auth::user();
        $sessionToken = Session::get('api_token');
        
        // Jika tidak ada token di session, generate token baru
        if (!$sessionToken) {
            $this->generateNewToken($user);
            return $next($request);
        }

        // Validasi token API
        if (!$this->isTokenValid($sessionToken, $user)) {
            // Token invalid, check apakah ada user lain yang login
            if ($this->hasOtherUserLoggedIn($user)) {
                // User lain sudah login, force logout user ini
                $this->forceLogout($request, 'User lain telah login. Silakan login ulang.');
                return $this->redirectToLogin($request, 'User lain telah login. Silakan login ulang.');
            } else {
                // Generate token baru jika tidak ada user lain
                $this->generateNewToken($user);
            }
        }

        return $next($request);
    }

    /**
     * Check apakah token API masih valid
     */
    private function isTokenValid($sessionToken, $user): bool
    {
        if (!$sessionToken || !$user) {
            return false;
        }

        // Extract plain token dari session token
        $tokenParts = explode('|', $sessionToken);
        if (count($tokenParts) !== 2) {
            return false;
        }

        $plainToken = $tokenParts[1];
        $hashedToken = hash('sha256', $plainToken);
        $cacheKey = 'tok_ok_' . $user->id;

        // Cache hasil validasi selama 5 menit agar tidak query DB setiap request
        return Cache::remember($cacheKey, 300, function () use ($user, $hashedToken) {
            return PersonalAccessToken::where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->where('token', $hashedToken)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->exists();
        });
    }

    /**
     * Check apakah ada user lain yang login (token baru dibuat)
     */
    private function hasOtherUserLoggedIn($currentUser): bool
    {
        $sessionUserId = Session::get('user_id');
        
        // Jika ada user_id di session tapi tidak match dengan current user
        if ($sessionUserId && $sessionUserId != $currentUser->id) {
            return true;
        }

        // Check apakah ada token aktif lain untuk user ini
        $activeTokens = PersonalAccessToken::where('tokenable_id', $currentUser->id)
            ->where('tokenable_type', get_class($currentUser))
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->count();

        // Jika tidak ada token aktif, kemungkinan user lain sudah login
        return $activeTokens === 0;
    }

    /**
     * Generate token baru untuk user
     */
    private function generateNewToken($user): void
    {
        // Invalidate cache token lama
        Cache::forget('tok_ok_' . $user->id);

        // Hapus token lama
        PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->delete();

        // Generate token baru
        $tokenName = config('app.name', 'Laravel') . '-' . $user->id . '-' . time();
        $token = $user->createToken($tokenName, ['*'], now()->addDays(30))->plainTextToken;
        
        // Simpan token di session
        Session::put('api_token', $token);
        Session::put('user_id', $user->id);
        Session::put('login_timestamp', now()->timestamp);
    }

    /**
     * Force logout user dan clear semua sessions
     */
    private function forceLogout(Request $request, string $reason = 'Session tidak valid'): void
    {
        $user = Auth::user();
        
        if ($user) {
            Cache::forget('tok_ok_' . $user->id);
            // Delete all tokens for this user
            PersonalAccessToken::where('tokenable_id', $user->id)
                ->where('tokenable_type', get_class($user))
                ->delete();
        }

        // Clear session
        Session::forget(['api_token', 'user_id', 'login_timestamp']);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Redirect ke login dengan pesan error
     */
    private function redirectToLogin(Request $request, string $message): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'error' => $message,
                'redirect' => route('login'),
                'force_logout' => true
            ], 401);
        }
        
        return redirect()->route('login')->with('error', $message);
    }
}