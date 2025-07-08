<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OAuthProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    // Middleware is now handled in routes instead of constructor

    /**
     * Redirect to OAuth provider
     */
    public function oauthRedirect($provider)
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid provider'], 400);
        }
    }

    /**
     * Handle OAuth callback
     */
    public function oauthCallback($provider)
    {
        try {
            $socialiteUser = Socialite::driver($provider)->user();
            
            // Check if OAuth provider already exists
            $oauthProvider = OAuthProvider::where([
                'provider' => $provider,
                'provider_id' => $socialiteUser->getId()
            ])->first();

            if ($oauthProvider) {
                // Existing user login
                $user = $oauthProvider->user;
            } else {
                // Check if user exists by email
                $user = User::where('email', $socialiteUser->getEmail())->first();
                
                if (!$user) {
                    // Create new user
                    $user = User::create([
                        'name' => $socialiteUser->getName(),
                        'email' => $socialiteUser->getEmail(),
                        'email_verified_at' => now(),
                        'password' => bcrypt(str()->random(16)), // Random password
                    ]);
                }

                // Create OAuth provider record
                OAuthProvider::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $socialiteUser->getId(),
                    'provider_token' => $socialiteUser->token,
                ]);
            }

            // Generate JWT token
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // 1 week in seconds
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'OAuth authentication failed'], 500);
        }
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Logout user
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh();
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }
}
