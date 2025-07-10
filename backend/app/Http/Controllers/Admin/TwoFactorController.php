<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function setup()
    {
        $user = auth()->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.manage')->with('warning', '2FA is already enabled');
        }

        $secret = $user->generateTwoFactorSecret();
        $qrCodeSvg = $user->getTwoFactorQrCodeSvg();

        return view('admin.two-factor.setup', [
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $secret,
            'user' => $user,
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Current password is incorrect.'],
            ]);
        }

        if (!$user->verifyTwoFactorCode($request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.'],
            ]);
        }

        $user->enableTwoFactor();
        $recoveryCodes = $user->getRecoveryCodes();

        Log::info('2FA enabled for admin user', ['user_id' => $user->id, 'email' => $user->email]);

        return view('admin.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ])->with('success', 'Two-factor authentication has been enabled successfully!');
    }

    public function challenge()
    {
        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        if (session('two_factor_confirmed')) {
            return redirect()->intended(route('filament.admin.pages.dashboard'));
        }

        return view('admin.two-factor.challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        $user = auth()->user();
        $isValidCode = $user->verifyTwoFactorCode($request->code);

        if (!$isValidCode) {
            $this->logFailedAttempt($user, 'Invalid 2FA code');

            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.'],
            ]);
        }

        session(['two_factor_confirmed' => true]);

        Log::info('2FA verification successful', ['user_id' => $user->id, 'email' => $user->email]);

        return redirect()->intended(route('filament.admin.pages.dashboard'))
            ->with('success', 'Two-factor authentication verified successfully!');
    }

    public function recoveryChallenge()
    {
        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        return view('admin.two-factor.recovery-challenge');
    }

    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string',
        ]);

        $user = auth()->user();
        $recoveryCode = str_replace(' ', '', strtoupper($request->recovery_code));

        if (!$user->useRecoveryCode($recoveryCode)) {
            $this->logFailedAttempt($user, 'Invalid recovery code');

            throw ValidationException::withMessages([
                'recovery_code' => ['The provided recovery code is invalid or has already been used.'],
            ]);
        }

        session(['two_factor_confirmed' => true]);

        Log::warning('2FA recovery code used', [
            'user_id' => $user->id,
            'email' => $user->email,
            'remaining_codes' => count($user->getRecoveryCodes()),
        ]);

        $message = 'Recovery code verified successfully! ';
        if (count($user->getRecoveryCodes()) <= 2) {
            $message .= 'Warning: You have only ' . count($user->getRecoveryCodes()) . ' recovery codes remaining.';
        }

        return redirect()->intended(route('filament.admin.pages.dashboard'))
            ->with('warning', $message);
    }

    public function manage()
    {
        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup');
        }

        return view('admin.two-factor.manage', [
            'user' => $user,
            'recoveryCodesCount' => count($user->getRecoveryCodes()),
        ]);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Current password is incorrect.'],
            ]);
        }

        $newCodes = $user->regenerateRecoveryCodes();

        Log::info('2FA recovery codes regenerated', ['user_id' => $user->id, 'email' => $user->email]);

        return view('admin.two-factor.recovery-codes', [
            'recoveryCodes' => $newCodes,
        ])->with('success', 'New recovery codes have been generated!');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Current password is incorrect.'],
            ]);
        }

        if (!$user->verifyTwoFactorCode($request->code)) {
            throw ValidationException::withMessages([
                'code' => ['The provided two-factor authentication code is invalid.'],
            ]);
        }

        $user->disableTwoFactor();

        Log::warning('2FA disabled for admin user', ['user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('admin.two-factor.setup')
            ->with('success', 'Two-factor authentication has been disabled.');
    }

    private function logFailedAttempt(User $user, string $reason)
    {
        \DB::table('admin_login_attempts')->insert([
            'user_id' => $user->id,
            'ip_address' => request()->getClientIp(),
            'user_agent' => request()->userAgent(),
            'email' => $user->email,
            'successful' => false,
            'failure_reason' => $reason,
            'attempted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::warning('Admin 2FA verification failed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
            'ip' => request()->getClientIp(),
        ]);
    }
}
