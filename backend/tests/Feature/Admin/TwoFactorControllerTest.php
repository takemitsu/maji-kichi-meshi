<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    // =============================================================================
    // setup() Tests
    // =============================================================================

    public function test_setup_displays_qr_code_for_new_2fa_setup(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.setup'));

        $response->assertOk();
        $response->assertViewIs('admin.two-factor.setup');
        $response->assertViewHas('qrCodeSvg');
        $response->assertViewHas('secret');
        $response->assertViewHas('user');

        // Check that 2FA secret was generated
        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_secret);
    }

    public function test_setup_redirects_if_2fa_already_enabled(): void
    {
        // Enable 2FA first
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.setup'));

        $response->assertRedirect(route('admin.two-factor.manage'));
        $response->assertSessionHas('warning', '2FA is already enabled');
    }

    // =============================================================================
    // confirm() Tests
    // =============================================================================

    public function test_confirm_enables_2fa_with_valid_code_and_password(): void
    {
        // Setup: generate secret
        $secret = $this->admin->generateTwoFactorSecret();

        // Generate valid TOTP code
        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.confirm'), [
                'code' => $validCode,
                'password' => 'password123',
            ]);

        $response->assertOk();
        $response->assertViewIs('admin.two-factor.recovery-codes');
        $response->assertViewHas('recoveryCodes');

        // Check 2FA is enabled
        $this->admin->refresh();
        $this->assertTrue($this->admin->hasTwoFactorEnabled());
        $this->assertNotNull($this->admin->two_factor_confirmed_at);
        $this->assertNotEmpty($this->admin->getRecoveryCodes());
    }

    public function test_confirm_fails_with_incorrect_password(): void
    {
        $secret = $this->admin->generateTwoFactorSecret();

        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.confirm'), [
                'code' => $validCode,
                'password' => 'wrongpassword',
            ]);

        $response->assertSessionHasErrors('password');

        // Check 2FA is NOT enabled
        $this->admin->refresh();
        $this->assertFalse($this->admin->hasTwoFactorEnabled());
    }

    public function test_confirm_fails_with_invalid_code(): void
    {
        $this->admin->generateTwoFactorSecret();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.confirm'), [
                'code' => '000000', // Invalid code
                'password' => 'password123',
            ]);

        $response->assertSessionHasErrors('code');

        // Check 2FA is NOT enabled
        $this->admin->refresh();
        $this->assertFalse($this->admin->hasTwoFactorEnabled());
    }

    public function test_confirm_validates_code_format(): void
    {
        $this->admin->generateTwoFactorSecret();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.confirm'), [
                'code' => 'abc', // Invalid format
                'password' => 'password123',
            ]);

        $response->assertSessionHasErrors('code');
    }

    // =============================================================================
    // challenge() Tests
    // =============================================================================

    public function test_challenge_displays_2fa_input_when_enabled(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.challenge'));

        $response->assertOk();
        $response->assertViewIs('admin.two-factor.challenge');
    }

    public function test_challenge_redirects_to_setup_when_2fa_not_enabled(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.challenge'));

        $response->assertRedirect(route('admin.two-factor.setup'));
    }

    public function test_challenge_redirects_to_dashboard_when_already_confirmed(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->withSession(['two_factor_confirmed' => true])
            ->get(route('admin.two-factor.challenge'));

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    // =============================================================================
    // verify() Tests
    // =============================================================================

    public function test_verify_succeeds_with_valid_code(): void
    {
        $secret = $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify'), [
                'code' => $validCode,
            ]);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('two_factor_confirmed', true);
    }

    public function test_verify_fails_with_invalid_code(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify'), [
                'code' => '000000',
            ]);

        $response->assertSessionHasErrors('code');
        $response->assertSessionMissing('two_factor_confirmed');
    }

    public function test_verify_logs_failed_attempt(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify'), [
                'code' => '000000',
            ]);

        // Check failed attempt was logged
        $this->assertDatabaseHas('admin_login_attempts', [
            'user_id' => $this->admin->id,
            'email' => $this->admin->email,
            'successful' => false,
            'failure_reason' => 'Invalid 2FA code',
        ]);
    }

    // =============================================================================
    // recoveryChallenge() Tests
    // =============================================================================

    public function test_recovery_challenge_displays_recovery_input(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.recovery-challenge'));

        $response->assertOk();
        $response->assertViewIs('admin.two-factor.recovery-challenge');
    }

    public function test_recovery_challenge_redirects_when_2fa_not_enabled(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.recovery-challenge'));

        $response->assertRedirect(route('admin.two-factor.setup'));
    }

    // =============================================================================
    // verifyRecovery() Tests
    // =============================================================================

    public function test_verify_recovery_succeeds_with_valid_code(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $recoveryCodes = $this->admin->getRecoveryCodes();
        $validRecoveryCode = $recoveryCodes[0];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify-recovery'), [
                'recovery_code' => $validRecoveryCode,
            ]);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $response->assertSessionHas('warning'); // Contains success message
        $response->assertSessionHas('two_factor_confirmed', true);

        // Check recovery code was used (removed from list)
        $this->admin->refresh();
        $remainingCodes = $this->admin->getRecoveryCodes();
        $this->assertNotContains($validRecoveryCode, $remainingCodes);
        $this->assertCount(7, $remainingCodes); // 8 - 1 = 7
    }

    public function test_verify_recovery_fails_with_invalid_code(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify-recovery'), [
                'recovery_code' => 'INVALIDCODE',
            ]);

        $response->assertSessionHasErrors('recovery_code');
        $response->assertSessionMissing('two_factor_confirmed');
    }

    public function test_verify_recovery_warns_when_few_codes_remaining(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $recoveryCodes = $this->admin->getRecoveryCodes();

        // Use 6 codes, leaving 2
        for ($i = 0; $i < 6; $i++) {
            $this->admin->useRecoveryCode($recoveryCodes[$i]);
        }

        $this->admin->refresh();
        $remainingCodes = $this->admin->getRecoveryCodes();
        $this->assertCount(2, $remainingCodes);

        // Use one more, leaving 1
        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify-recovery'), [
                'recovery_code' => $remainingCodes[0],
            ]);

        $response->assertRedirect(route('filament.admin.pages.dashboard'));
        $response->assertSessionHas('warning');

        // Check warning message contains count
        $warningMessage = session('warning');
        $this->assertStringContainsString('1 recovery codes remaining', $warningMessage);
    }

    public function test_verify_recovery_logs_failed_attempt(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $this->actingAs($this->admin)
            ->post(route('admin.two-factor.verify-recovery'), [
                'recovery_code' => 'INVALIDCODE',
            ]);

        // Check failed attempt was logged
        $this->assertDatabaseHas('admin_login_attempts', [
            'user_id' => $this->admin->id,
            'email' => $this->admin->email,
            'successful' => false,
            'failure_reason' => 'Invalid recovery code',
        ]);
    }

    // =============================================================================
    // manage() Tests
    // =============================================================================

    public function test_manage_displays_management_page_when_2fa_enabled(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.manage'));

        $response->assertOk();
        $response->assertViewIs('admin.two-factor.manage');
        $response->assertViewHas('user');
        $response->assertViewHas('recoveryCodesCount', 8);
    }

    public function test_manage_redirects_to_setup_when_2fa_not_enabled(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.two-factor.manage'));

        $response->assertRedirect(route('admin.two-factor.setup'));
    }

    // =============================================================================
    // regenerateRecoveryCodes() Tests
    // =============================================================================

    public function test_regenerate_recovery_codes_with_valid_password(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $oldCodes = $this->admin->getRecoveryCodes();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.regenerate-recovery-codes'), [
                'password' => 'password123',
            ]);

        $response->assertOk();
        $response->assertViewIs('admin.two-factor.recovery-codes');
        $response->assertViewHas('recoveryCodes');

        // Check new codes were generated
        $this->admin->refresh();
        $newCodes = $this->admin->getRecoveryCodes();

        $this->assertCount(8, $newCodes);
        $this->assertNotEquals($oldCodes, $newCodes);
    }

    public function test_regenerate_recovery_codes_fails_with_incorrect_password(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.regenerate-recovery-codes'), [
                'password' => 'wrongpassword',
            ]);

        $response->assertSessionHasErrors('password');
    }

    // =============================================================================
    // disable() Tests
    // =============================================================================

    public function test_disable_2fa_with_valid_code_and_password(): void
    {
        $secret = $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.disable'), [
                'code' => $validCode,
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('admin.two-factor.setup'));
        $response->assertSessionHas('success');

        // Check 2FA is disabled
        $this->admin->refresh();
        $this->assertFalse($this->admin->hasTwoFactorEnabled());
        $this->assertNull($this->admin->two_factor_secret);
        $this->assertNull($this->admin->two_factor_confirmed_at);
    }

    public function test_disable_fails_with_incorrect_password(): void
    {
        $secret = $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.disable'), [
                'code' => $validCode,
                'password' => 'wrongpassword',
            ]);

        $response->assertSessionHasErrors('password');

        // Check 2FA is still enabled
        $this->admin->refresh();
        $this->assertTrue($this->admin->hasTwoFactorEnabled());
    }

    public function test_disable_fails_with_invalid_code(): void
    {
        $this->admin->generateTwoFactorSecret();
        $this->admin->enableTwoFactor();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.two-factor.disable'), [
                'code' => '000000',
                'password' => 'password123',
            ]);

        $response->assertSessionHasErrors('code');

        // Check 2FA is still enabled
        $this->admin->refresh();
        $this->assertTrue($this->admin->hasTwoFactorEnabled());
    }

    // =============================================================================
    // Authentication Tests
    // =============================================================================

    public function test_unauthenticated_user_cannot_access_2fa_routes(): void
    {
        $routes = [
            ['get', route('admin.two-factor.setup')],
            ['post', route('admin.two-factor.confirm')],
            ['get', route('admin.two-factor.challenge')],
            ['post', route('admin.two-factor.verify')],
            ['get', route('admin.two-factor.recovery-challenge')],
            ['post', route('admin.two-factor.verify-recovery')],
            ['get', route('admin.two-factor.manage')],
            ['post', route('admin.two-factor.regenerate-recovery-codes')],
            ['post', route('admin.two-factor.disable')],
        ];

        foreach ($routes as [$method, $url]) {
            $response = $this->$method($url);
            $response->assertRedirect(); // Should redirect to login
        }
    }
}
