# 管理者認証セキュリティ強化タスク

**作成日時**: 2025-07-09 11:36:00 JST  
**対象**: 管理者認証システムのセキュリティ強化  
**重要度**: 🔴 **High** (セキュリティリスク)

## 🚨 **現在のセキュリティ問題**

### 現状の脆弱性
```php
// 現在の認証: ID/Password のみ
- 単一要素認証 (SFA)
- ブルートフォース攻撃に脆弱
- 認証情報漏洩時の影響大
- セッション固定攻撃の可能性
```

### 現代のセキュリティ基準
- **NIST SP 800-63B**: 多要素認証 (MFA) 必須
- **ISO 27001**: 管理者アクセスには強化認証
- **PCI DSS**: カード情報扱いシステムでは MFA 必須
- **業界標準**: 管理者権限には 2FA 以上

---

## 🛡️ **セキュリティ強化提案**

### 📋 **Phase 1: 多要素認証 (MFA) 実装**

#### 1. TOTP (Time-based OTP) 実装
```php
// 必要なパッケージ
composer require pragmarx/google2fa-laravel
composer require bacon/bacon-qr-code

// 実装項目
- [ ] Google Authenticator 対応
- [ ] QRコード生成
- [ ] バックアップコード生成
- [ ] 管理者設定画面
```

#### 2. SMS認証 (バックアップ)
```php
// 必要なパッケージ
composer require twilio/sdk
// または
composer require nexmo/laravel

// 実装項目
- [ ] SMS送信機能
- [ ] 電話番号登録
- [ ] OTP生成・検証
```

#### 3. Email認証 (バックアップ)
```php
// Laravel標準機能で実装可能
// 実装項目
- [ ] Email OTP送信
- [ ] 時間制限付きトークン
- [ ] 管理者メール設定
```

### 📋 **Phase 2: 追加セキュリティ機能**

#### 4. IP制限機能
```php
// 実装項目
- [ ] 許可IPアドレス設定
- [ ] 地理的制限
- [ ] VPN検知
- [ ] 不正アクセス検知
```

#### 5. セッション管理強化
```php
// 実装項目
- [ ] セッション固定攻撃対策
- [ ] 同時セッション制限
- [ ] 自動ログアウト
- [ ] デバイス管理
```

#### 6. 監査ログ
```php
// 実装項目
- [ ] 管理者操作ログ
- [ ] ログイン試行履歴
- [ ] 異常検知アラート
- [ ] ログエクスポート
```

---

## 🔧 **具体的な実装計画**

### **Step 1: TOTP実装** (2-3日)

#### データベース設計
```sql
-- users テーブル拡張
ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN two_factor_recovery_codes JSON NULL;
ALTER TABLE users ADD COLUMN two_factor_confirmed_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE;

-- 新規テーブル
CREATE TABLE admin_login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    successful BOOLEAN DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
);
```

#### モデル拡張
```php
// User.php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'role', 'status',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'
    ];

    protected $hidden = [
        'password', 'remember_token', 'role', 'status',
        'two_factor_secret', 'two_factor_recovery_codes'
    ];

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function generateTwoFactorSecret(): string
    {
        $this->two_factor_secret = encrypt(Google2FA::generateSecretKey());
        $this->save();
        return decrypt($this->two_factor_secret);
    }

    public function getTwoFactorQrCodeUrl(): string
    {
        return Google2FA::getQRCodeUrl(
            config('app.name'),
            $this->email,
            decrypt($this->two_factor_secret)
        );
    }
}
```

#### コントローラー実装
```php
// TwoFactorController.php
class TwoFactorController extends Controller
{
    public function enable()
    {
        $user = auth()->user();
        $secret = $user->generateTwoFactorSecret();
        
        return view('admin.two-factor.enable', [
            'qrCodeUrl' => $user->getTwoFactorQrCodeUrl(),
            'secret' => $secret
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = auth()->user();
        $secret = decrypt($user->two_factor_secret);
        
        if (!Google2FA::verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => '認証コードが正しくありません']);
        }

        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = encrypt(json_encode($this->generateRecoveryCodes()));
        $user->save();

        return redirect()->route('admin.two-factor.recovery-codes');
    }

    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn () => Str::random(10))->toArray();
    }
}
```

#### 認証ミドルウェア拡張
```php
// FilamentAdminMiddleware.php
class FilamentAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();
        
        // 基本認証チェック
        if (!$user || !$user->isModerator()) {
            return redirect()->route('filament.admin.auth.login');
        }

        // 2FA必須チェック
        if ($user->isAdmin() && !$user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.two-factor.enable');
        }

        // 2FA確認チェック
        if ($user->hasTwoFactorEnabled() && !session('two_factor_confirmed')) {
            return redirect()->route('admin.two-factor.challenge');
        }

        return $next($request);
    }
}
```

### **Step 2: IP制限機能** (1-2日)

#### 設定ファイル
```php
// config/admin-security.php
return [
    'ip_whitelist' => [
        '127.0.0.1',
        '192.168.1.0/24',
        // 許可IPアドレス
    ],
    'geo_restriction' => [
        'enabled' => true,
        'allowed_countries' => ['JP'],
    ],
    'max_login_attempts' => 5,
    'lockout_duration' => 15, // minutes
];
```

#### IP制限ミドルウェア
```php
// AdminIpRestrictionMiddleware.php
class AdminIpRestrictionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->getClientIp();
        $allowedIps = config('admin-security.ip_whitelist');

        if (!$this->isIpAllowed($clientIp, $allowedIps)) {
            Log::warning('Admin access denied from IP: ' . $clientIp);
            abort(403, 'Access denied from this IP address');
        }

        return $next($request);
    }

    private function isIpAllowed(string $ip, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInRange($ip, $allowedIp)) {
                    return true;
                }
            } elseif ($ip === $allowedIp) {
                return true;
            }
        }
        return false;
    }
}
```

### **Step 3: 監査ログ** (1-2日)

#### 監査ログモデル
```php
// AdminAuditLog.php
class AdminAuditLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 
        'old_values', 'new_values', 'ip_address', 'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function logAction(string $action, Model $model = null, array $oldValues = [], array $newValues = [])
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->getClientIp(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

---

## 🚀 **実装優先度**

### 🔴 **Phase 1: 緊急対応** (1週間)
1. **TOTP実装** (3日)
2. **IP制限機能** (2日)
3. **基本監査ログ** (2日)

### 🟡 **Phase 2: 強化対応** (1-2週間)
4. **SMS認証** (3日)
5. **セッション管理強化** (2日)
6. **異常検知システム** (3日)

### 🟢 **Phase 3: 高度な機能** (将来)
7. **デバイス管理**
8. **生体認証**
9. **SSO連携**

---

## 📋 **実装チェックリスト**

### セキュリティ要件
- [ ] 多要素認証 (MFA) 実装
- [ ] IP制限機能
- [ ] セッション管理強化
- [ ] 監査ログ機能
- [ ] ブルートフォース対策
- [ ] セキュリティヘッダー設定

### テスト要件
- [ ] 認証フローテスト
- [ ] セキュリティテスト
- [ ] 侵入テスト
- [ ] 負荷テスト

### 運用要件
- [ ] 管理者向けドキュメント
- [ ] インシデント対応手順
- [ ] 定期的なセキュリティ監査
- [ ] バックアップ・リカバリ手順

---

## 🔐 **セキュリティ基準達成目標**

### 現在のセキュリティレベル
- **認証**: 🔴 弱い (ID/Password のみ)
- **権限管理**: 🟡 普通 (Role-based)
- **監査**: 🔴 なし
- **総合**: 🔴 **不十分**

### 実装後の目標レベル
- **認証**: 🟢 強い (MFA + IP制限)
- **権限管理**: 🟢 強い (Role + 2FA)
- **監査**: 🟢 完備 (全操作ログ)
- **総合**: 🟢 **企業レベル**

---

## ⚠️ **重要な注意点**

### 実装時の考慮事項
1. **管理者の利便性**: セキュリティと使いやすさのバランス
2. **バックアップ手順**: 2FA デバイス紛失時の対応
3. **段階的実装**: 既存管理者への影響最小化
4. **緊急時対応**: システム障害時のアクセス手順

### 運用時の考慮事項
1. **定期的な見直し**: セキュリティ設定の定期監査
2. **管理者教育**: セキュリティ意識向上
3. **インシデント対応**: 不正アクセス検知時の手順
4. **アップデート**: セキュリティパッチの適用

---

**結論**: 現在の管理者認証は確実にセキュリティホールです。Phase 1の実装により、企業レベルのセキュリティ基準に到達できます。

**推奨**: 即座に Phase 1 の実装を開始することを強く推奨します。