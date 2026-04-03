<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/auth/google/redirect
     * Returns the Google OAuth URL for the frontend to redirect to.
     */
    public function redirect()
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return $this->success(['url' => $url]);
    }

    /**
     * GET /api/auth/google/callback
     * Google redirects here after user grants permission.
     * Creates or finds the user, issues a Sanctum token, then
     * redirects the browser back to the frontend with the token.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
            return redirect($frontendUrl . '/auth/login?error=google_failed');
        }

        // Find or create user
        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'              => $googleUser->getName(),
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'password'          => bcrypt(Str::random(32)), // random unusable password
                'email_verified_at' => now(), // Google emails are pre-verified
                'role'              => 'guest',
            ]
        );

        // If user existed but never linked Google, link it now
        if (! $user->google_id) {
            $user->update([
                'google_id'         => $googleUser->getId(),
                'avatar'            => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
        }

        $token = $user->createToken('google-auth')->plainTextToken;

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        // Redirect to frontend with token — frontend reads it from URL and stores it
        return redirect($frontendUrl . '/auth/google/callback?token=' . $token . '&user=' . urlencode(json_encode(new UserResource($user))));
    }
}
