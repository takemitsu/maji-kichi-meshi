<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements FilamentUser, JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_enabled',
        'profile_image_filename',
        'profile_image_original_name',
        'profile_image_thumbnail_path',
        'profile_image_small_path',
        'profile_image_medium_path',
        'profile_image_large_path',
        'profile_image_file_size',
        'profile_image_mime_type',
        'profile_image_uploaded_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'role',
        'status',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'profile_image_uploaded_at' => 'datetime',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * OAuth providers relationship
     */
    public function oauthProviders()
    {
        return $this->hasMany(OAuthProvider::class);
    }

    /**
     * Reviews relationship
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Rankings relationship
     */
    public function rankings()
    {
        return $this->hasMany(Ranking::class);
    }

    /**
     * Review likes relationship
     */
    public function reviewLikes()
    {
        return $this->hasMany(ReviewLike::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is moderator (admin or moderator)
     */
    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user can access Filament admin panel
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->role === 'admin' || $this->role === 'moderator';
    }

    // =============================================================================
    // 2FA (Two-Factor Authentication) Methods
    // =============================================================================

    /**
     * Check if two-factor authentication is enabled
     */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Generate a new two-factor secret
     */
    public function generateTwoFactorSecret(): string
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $this->two_factor_secret = encrypt($secret);
        $this->two_factor_enabled = true;
        $this->save();

        return $secret;
    }

    /**
     * Get the decrypted two-factor secret
     */
    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret ? decrypt($this->two_factor_secret) : null;
    }

    /**
     * Get the two-factor QR code URL
     */
    public function getTwoFactorQrCodeUrl(): string
    {
        $google2fa = new Google2FA;
        $secret = $this->getTwoFactorSecret();

        if (!$secret) {
            throw new \Exception('Two-factor secret not set');
        }

        return $google2fa->getQRCodeUrl(
            config('app.name'),
            $this->email,
            $secret
        );
    }

    /**
     * Get the two-factor QR code SVG
     */
    public function getTwoFactorQrCodeSvg(): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($this->getTwoFactorQrCodeUrl());
    }

    /**
     * Verify a two-factor code
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        $google2fa = new Google2FA;
        $secret = $this->getTwoFactorSecret();

        if (!$secret) {
            return false;
        }

        return $google2fa->verifyKey($secret, $code);
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(): void
    {
        $this->two_factor_confirmed_at = now();
        $this->two_factor_enabled = true;
        $this->two_factor_recovery_codes = encrypt(json_encode($this->generateRecoveryCodes()));
        $this->save();
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->two_factor_enabled = false;
        $this->save();
    }

    /**
     * Generate recovery codes
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => strtoupper(Str::random(10)))
            ->toArray();
    }

    /**
     * Get recovery codes
     */
    public function getRecoveryCodes(): array
    {
        return $this->two_factor_recovery_codes
            ? json_decode(decrypt($this->two_factor_recovery_codes), true)
            : [];
    }

    /**
     * Use a recovery code
     */
    public function useRecoveryCode(string $code): bool
    {
        $recoveryCodes = $this->getRecoveryCodes();

        if (!in_array($code, $recoveryCodes)) {
            return false;
        }

        // Remove used recovery code
        $remainingCodes = array_filter($recoveryCodes, fn ($c) => $c !== $code);
        $this->two_factor_recovery_codes = encrypt(json_encode(array_values($remainingCodes)));
        $this->save();

        return true;
    }

    /**
     * Regenerate recovery codes
     */
    public function regenerateRecoveryCodes(): array
    {
        $newCodes = $this->generateRecoveryCodes();
        $this->two_factor_recovery_codes = encrypt(json_encode($newCodes));
        $this->save();

        return $newCodes;
    }

    // =============================================================================
    // Profile Image Methods
    // =============================================================================

    /**
     * Delete profile image files
     */
    public function deleteProfileImage(): void
    {
        if (!$this->hasProfileImage()) {
            return;
        }

        // Delete physical files (smallサイズのみ)
        if ($this->profile_image_small_path && Storage::exists($this->profile_image_small_path)) {
            Storage::delete($this->profile_image_small_path);
        }

        // Clear database fields
        $this->profile_image_filename = null;
        $this->profile_image_original_name = null;
        $this->profile_image_thumbnail_path = null;
        $this->profile_image_small_path = null;
        $this->profile_image_medium_path = null;
        $this->profile_image_large_path = null;
        $this->profile_image_file_size = null;
        $this->profile_image_mime_type = null;
        $this->profile_image_uploaded_at = null;
        $this->save();
    }

    // =============================================================================
    // Profile Image Methods
    // =============================================================================

    /**
     * Check if user has a profile image
     */
    public function hasProfileImage(): bool
    {
        return !empty($this->profile_image_filename) && !empty($this->profile_image_small_path);
    }

    /**
     * Get profile image URLs for all sizes
     */
    public function getProfileImageUrls(): array
    {
        $appUrl = config('app.url');

        return [
            'small' => $this->profile_image_small_path
                ? "{$appUrl}/storage/{$this->profile_image_small_path}"
                : null,
            'original' => $this->profile_image_small_path
                ? "{$appUrl}/storage/{$this->profile_image_small_path}"
                : null,
        ];
    }

    /**
     * Get profile image URL for specific size
     */
    public function getProfileImageUrl(string $size = 'small'): ?string
    {
        $urls = $this->getProfileImageUrls();

        return $urls[$size] ?? $urls['small'];
    }
}
