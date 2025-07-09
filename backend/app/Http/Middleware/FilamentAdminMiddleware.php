<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;

class FilamentAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        
        if (!$user || !$user->isModerator()) {
            abort(403, 'Unauthorized');
        }

        // 2FA必須チェック（管理者のみ）
        if ($user->isAdmin() && !$user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.setup')
                ->with('warning', '管理者はTwo-Factor Authentication (2FA)の設定が必要です。');
        }

        // 2FA認証チェック
        if ($user->hasTwoFactorEnabled() && !session('two_factor_confirmed')) {
            return redirect()->route('admin.two-factor.challenge')
                ->with('info', 'Two-Factor Authenticationコードを入力してください。');
        }

        return $next($request);
    }
}