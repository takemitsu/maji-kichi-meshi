<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class User extends Authenticatable implements JWTSubject
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
        return $this->isModerator() && $this->isActive();
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
        $google2fa = new Google2FA();
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
        $google2fa = new Google2FA();
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
            new SvgImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        return $writer->writeString($this->getTwoFactorQrCodeUrl());
    }

    /**
     * Verify a two-factor code
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        $google2fa = new Google2FA();
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
        $remainingCodes = array_filter($recoveryCodes, fn($c) => $c !== $code);
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
}
