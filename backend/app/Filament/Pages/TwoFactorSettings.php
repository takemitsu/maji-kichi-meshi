<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.two-factor-settings';

    protected static ?string $navigationLabel = '2FA設定';

    protected static ?string $title = 'Two-Factor Authentication 設定';

    protected static ?string $navigationGroup = 'システム設定';

    protected static ?int $navigationSort = 200;

    public function mount(): void
    {
        $user = auth()->user();
        
        if (!$user || !$user->isModerator()) {
            abort(403);
        }
    }

    public function regenerateRecoveryCodes(): void
    {
        try {
            $this->validate([
                'password' => 'required|string'
            ]);

            $user = auth()->user();

            if (!Hash::check($this->password, $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'パスワードが正しくありません。'
                ]);
            }

            $newCodes = $user->regenerateRecoveryCodes();

            $this->redirect(route('admin.two-factor.recovery-codes', ['codes' => $newCodes]));
            
        } catch (Halt $exception) {
            return;
        }
    }

    public function redirectToSetup(): void
    {
        $this->redirect(route('admin.two-factor.setup'));
    }

    public function redirectToManage(): void
    {
        $this->redirect(route('admin.two-factor.manage'));
    }

    public function getUser()
    {
        return auth()->user();
    }

    public function getRecoveryCodesCount(): int
    {
        return count($this->getUser()->getRecoveryCodes());
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->getUser()->hasTwoFactorEnabled();
    }
}