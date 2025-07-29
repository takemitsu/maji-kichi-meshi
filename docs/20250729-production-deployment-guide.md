# 本番環境デプロイガイド - Laravel Filament管理画面対応版

**作成日**: 2025-07-29  
**対象**: マジキチメシアプリケーション  
**環境**: Sakura VPS (Ubuntu + nginx + PHP8.3)

## 概要

Laravel Filament管理画面を含む完全なアプリケーションの本番環境デプロイ手順とトラブルシューティングガイド。

## 前提条件

### サーバー環境
- Ubuntu 24.04
- nginx
- PHP 8.3 + PHP-FPM
- MySQL/PostgreSQL
- Git

### OAuth設定
**Google Cloud Console**で以下を設定：
1. プロジェクト作成・選択
2. APIs & Services → Credentials → OAuth 2.0 Client IDs作成
3. Authorized redirect URIs: `https://maji-kichi-meshi.takemitsu.net/api/auth/google/callback`
4. Client IDとClient Secretを取得

### 権限設定
```bash
# PHP-FPMソケット権限（重要）
sudo usermod -a -G nginx www-data
sudo systemctl restart php8.3-fpm
```

## デプロイ手順

### 1. 基本デプロイ

```bash
# デプロイメント環境に移動
cd ~/deployment/maji-kichi-meshi/

# 最新コードを取得
git pull origin main

# デプロイスクリプト実行
./scripts/deploy.sh
```

### 2. Filament管理画面の初期設定

#### 2.1 管理者権限確認
```bash
cd /var/www/maji-kichi-backend
mysql -u maji_kichi_user -p maji_kichi_meshi -e "SELECT id, name, email, role, status FROM users WHERE role='admin';"
```

#### 2.2 2FA設定（管理者必須）
1. ブラウザで `https://maji-kichi-meshi.takemitsu.net/admin` にアクセス
2. 2FA設定ページにリダイレクトされる
3. QRコードをGoogle Authenticator等でスキャン
4. 確認コードを入力して2FAを有効化

## トラブルシューティング

### 403 Forbidden エラー

#### 原因1: PHP-FPMソケット権限問題
```bash
# 症状確認
sudo -u www-data test -r /run/php/php8.3-fpm.sock && echo "OK" || echo "NG"

# 解決方法
sudo usermod -a -G nginx www-data
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

#### 原因2: FilamentUserインターフェース未実装
```bash
# UserモデルでFilamentUserインターフェース実装確認
grep -n "FilamentUser" /var/www/maji-kichi-backend/app/Models/User.php
grep -n "canAccessPanel" /var/www/maji-kichi-backend/app/Models/User.php
```

**解決方法**: UserモデルにFilamentUserインターフェースとcanAccessPanel()メソッドを実装

#### 原因3: nginx設定問題
```bash
# admin用location設定確認
sudo nginx -T | grep -A10 "location.*admin"
```

**正しい設定例**:
```nginx
location ^~ /admin {
    root /var/www/maji-kichi-backend/public;
    try_files $uri $uri/ /index.php?$query_string;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 500 Internal Server Error

#### Laravel ログ確認
```bash
tail -f /var/www/maji-kichi-backend/storage/logs/laravel-$(date +%Y-%m-%d).log
```

#### よくあるエラーと解決方法

**メソッド重複エラー**:
```
Cannot redeclare App\Models\User::canAccessPanel()
```
→ UserモデルでcanAccessPanel()メソッドが重複定義されている

**キャッシュ関連エラー**:
```bash
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan cache:clear
sudo systemctl restart php8.3-fpm
```

### SPA 404エラー（管理画面にアクセスできない）

#### 症状
- `/admin`にアクセスするとSPAの404ページが表示される
- `/_nuxt/error-404.4oxyXxx0.css`が読み込まれる

#### 原因と解決
nginx設定でlocation優先順位の問題：

```nginx
# 正しい順序（adminを最優先）
location ^~ /admin {
    # Laravel Filament設定
}

location / {
    # SPA設定
    try_files $uri $uri/ /index.html;
}
```

## 動作確認チェックリスト

### フロントエンド
- [ ] `https://maji-kichi-meshi.takemitsu.net/` でSPAが表示される
- [ ] OAuthログインが正常に動作する
- [ ] 店舗一覧・レビュー機能が動作する

### 管理画面
- [ ] `https://maji-kichi-meshi.takemitsu.net/admin` でFilamentにアクセスできる
- [ ] 2FA設定が正常に動作する
- [ ] ダッシュボードが表示される
- [ ] 各リソース（Users, Shops, Reviews等）が表示される

### API
- [ ] `https://maji-kichi-meshi.takemitsu.net/api/shops` でAPI応答がある
- [ ] JWT認証が正常に動作する

## セキュリティ設定

### 管理者アカウント
- 管理者は必ず2FA設定を完了すること
- 強固なパスワードを設定すること
- 定期的なパスワード変更を推奨

### nginx設定
- SSL証明書の有効期限確認
- セキュリティヘッダーの設定確認
- 不要なアクセスログの定期削除

## 参考資料

- [CLAUDE.md](../CLAUDE.md) - プロジェクト全体設定
- [database-er-diagram.md](database-er-diagram.md) - データベース設計
- Laravel Filament公式ドキュメント
- nginx公式ドキュメント

## 更新履歴

- 2025-07-29: 初版作成（管理画面403エラー解決含む）