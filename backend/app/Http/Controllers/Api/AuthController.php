<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\OAuthProvider;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponseTrait;
    // Middleware is now handled in routes instead of constructor

    /**
     * Redirect to OAuth provider
     */
    public function oauthRedirect($provider)
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid OAuth provider', 400);
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
                'provider_id' => $socialiteUser->getId(),
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

            // Build success callback URL with token data
            $callbackUrl = config('app.frontend_url') . '/auth/callback?' . http_build_query([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60, // 1 week in seconds
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'success' => 'true',
            ]);

            return redirect($callbackUrl);
        } catch (\Exception $e) {
            // Build error callback URL
            $errorUrl = config('app.frontend_url') . '/auth/callback?' . http_build_query([
                'error' => 'oauth_failed',
                'error_description' => 'OAuth authentication failed',
                'success' => 'false',
            ]);

            return redirect($errorUrl);
        }
    }

    /**
     * Get authenticated user
     */
    public function me()
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->unauthorizedResponse('User not authenticated');
            }

            return $this->successResponse($user);
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve user information');
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return $this->successResponse(null, 'Successfully logged out');
        } catch (JWTException $e) {
            return $this->serverErrorResponse('Failed to logout');
        }
    }

    /**
     * Get JWT token info (for debugging/validation)
     */
    public function tokenInfo()
    {
        try {
            $token = JWTAuth::getToken();
            $payload = JWTAuth::getPayload($token);

            return $this->successResponse([
                'token' => $token->get(),
                'payload' => $payload->toArray(),
                'expires_at' => $payload->get('exp'),
                'issued_at' => $payload->get('iat'),
                'user_id' => $payload->get('sub'),
            ]);
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Invalid token');
        }
    }
}
