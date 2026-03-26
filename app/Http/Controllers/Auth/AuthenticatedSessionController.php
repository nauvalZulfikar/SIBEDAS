<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AuthenticatedSessionController extends Controller
{
      /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.signin');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Ambil user yang sedang login
        $user = Auth::user();

        // Hapus token lama jika ada
        \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->delete();

        // Buat token untuk API dengan scope dan expiration
        $tokenName = config('app.name', 'Laravel') . '-' . $user->id . '-' . time();
        
        // Token dengan scope (opsional)
        $token = $user->createToken($tokenName, ['*'], now()->addDays(30))->plainTextToken;
        
        // Simpan token di session untuk digunakan di frontend
        session(['api_token' => $token]);
        
        // Simpan timestamp login untuk validasi multi-user
        session(['login_timestamp' => now()->timestamp]);
        session(['user_id' => $user->id]);

        // Append menu_id dynamically to HOME
        $menuId = optional(\App\Models\Menu::where('name', 'Dashboard Pimpinan SIMBG')->first())->id;
        $home = RouteServiceProvider::HOME . ($menuId ? ('?menu_id=' . $menuId) : '');
        return redirect()->intended($home);
    }

    /**
     * Generate API token for authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateApiToken(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Delete existing tokens
        \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->delete();

        // Generate new token
        $tokenName = config('app.name', 'Laravel') . '-' . $user->id . '-' . time();
        $token = $user->createToken($tokenName, ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 30 * 24 * 60 * 60, // 30 days in seconds
        ]);
    }

    /**
     * Revoke API token for authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeApiToken(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked successfully']);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        if($request->user()){
            \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $request->user()->id)
                ->where('tokenable_type', get_class($request->user()))
                ->delete();
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}
