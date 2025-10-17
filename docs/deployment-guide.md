# デプロイメントガイド

**作成日**: 2025-07-29
**最終更新**: 2025-10-17
**対象**: マジキチメシアプリケーション
**環境**: Sakura VPS (Ubuntu + nginx + PHP8.3)

## 概要

Laravel API + Laravel Filament管理画面 + Vue/Nuxt SPA の本番環境デプロイガイド。

自動デプロイスクリプト `deploy.sh` を使った簡単デプロイ手順を中心に説明します。

## 前提条件

### サーバー環境
- Ubuntu 24.04
- nginx 1.28.0
- PHP 8.3 + PHP-FPM
- MySQL 8.0
- Git
- Node.js v18以上
- Composer

### 初回セットアップ済み項目
- `/var/www/maji-kichi-backend/` - Laravel API
- `/var/www/maji-kichi-frontend/` - Nuxt SPA
- `~/deployment/maji-kichi-meshi/` - デプロイ用リポジトリ
- SSL証明書（Let's Encrypt）
- nginx設定ファイル

### OAuth設定
**Google Cloud Console**で以下を設定：
1. OAuth 2.0 Client IDs作成
2. Authorized redirect URIs: `https://maji-kichi-meshi.takemitsu.net/api/auth/google/callback`
3. Client IDとClient Secretを取得し、`.env`に設定

## 🚀 デプロイ手順（推奨）

### deploy.shを使った自動デプロイ

**最も簡単な方法です。以下の作業を自動実行します：**

```bash
# サーバーにSSH接続後
cd ~/deployment/maji-kichi-meshi

# 最新コードを取得
git pull origin main

# デプロイスクリプト実行（自動バックアップ・ビルド・配置）
./deploy.sh
```

### deploy.shが実行する内容

1. **バックアップ作成** - 既存ファイルのバックアップ（タイムスタンプ付き）
2. **ソースコード更新** - git pullで最新コード取得
3. **バックエンド更新**
   - `.env`設定確認
   - Composer依存関係更新
   - データベースマイグレーション実行
   - Laravel本番用キャッシュ生成
4. **フロントエンド更新**
   - `.env`設定確認
   - npm依存関係更新（package.json変更時のみ）
   - Nuxt SPAビルド（`npm run generate`）
5. **本番環境デプロイ**
   - `/var/www/maji-kichi-backend/` にバックエンド配置
   - `/var/www/maji-kichi-frontend/` にフロントエンド配置
   - 適切な権限設定
6. **サービス再起動**
   - PHP-FPM・nginx再起動
7. **動作確認**
   - API・フロントエンド・管理画面の疎通確認

### デプロイログの確認

```bash
# デプロイ中のログ確認
tail -f /var/log/nginx/error.log

# Laravel ログ確認
sudo tail -f /var/www/maji-kichi-backend/storage/logs/laravel-$(date +%Y-%m-%d).log
```

## 📋 手動デプロイ手順（参考）

deploy.shが使えない場合の手動手順：

### 1. バックエンド手動デプロイ

```bash
cd ~/deployment/maji-kichi-meshi/backend

# 依存関係更新
composer install --optimize-autoloader --no-dev

# マイグレーション
php artisan migrate --force

# ファイル配置
sudo cp -r * /var/www/maji-kichi-backend/
sudo chown -R www-data:www-data /var/www/maji-kichi-backend
sudo chmod -R 755 /var/www/maji-kichi-backend
sudo chmod -R 775 /var/www/maji-kichi-backend/storage /var/www/maji-kichi-backend/bootstrap/cache

# キャッシュ生成
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# サービス再起動
sudo systemctl restart php8.3-fpm
```

### 2. フロントエンド手動デプロイ

```bash
cd ~/deployment/maji-kichi-meshi/frontend

# ビルド
npm ci
npm run generate

# ファイル配置
sudo rm -rf /var/www/maji-kichi-frontend/*
sudo cp -r .output/public/* /var/www/maji-kichi-frontend/
sudo chown -R www-data:www-data /var/www/maji-kichi-frontend

# nginx再起動
sudo nginx -t
sudo systemctl reload nginx
```

## 🔧 Filament管理画面の初期設定

### 初回デプロイ時のみ実施

#### 1. 管理者アカウント作成

```bash
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan admin:create
# Email address: admin@your-domain.com
# Full name: System Admin
# Password: [強固なパスワード]
# Select role: admin
```

#### 2. 2FA設定（管理者必須）

1. `https://maji-kichi-meshi.takemitsu.net/admin` にアクセス
2. 2FA設定ページにリダイレクトされる
3. QRコードをGoogle Authenticator等でスキャン
4. 確認コードを入力して2FAを有効化

## 🔍 トラブルシューティング

### 1. 403 Forbidden エラー（管理画面）

**原因**: PHP-FPMソケット権限問題

```bash
# 権限確認
sudo -u www-data test -r /run/php/php8.3-fpm.sock && echo "OK" || echo "NG"

# 解決方法
sudo usermod -a -G nginx www-data
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

### 2. 500 Internal Server Error

**原因**: キャッシュ問題

```bash
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan cache:clear
sudo systemctl restart php8.3-fpm
```

### 3. SPA 404エラー（/adminにアクセスできない）

**原因**: nginx設定の優先順位問題

nginx設定で`location ^~ /admin`を`location /`より先に記述する必要があります。

```nginx
# 正しい順序
location ^~ /admin {
    # Laravel Filament設定
}

location /api {
    # Laravel API設定
}

location / {
    # SPA設定
    try_files $uri $uri/ /index.html;
}
```

### 4. フロントエンドビルドエラー

**原因**: Node.js メモリ不足

```bash
# メモリ上限を引き上げてビルド
export NODE_OPTIONS="--max-old-space-size=4096"
npm run generate
```

### 5. マイグレーションエラー

```bash
# マイグレーション状態確認
php artisan migrate:status

# 失敗したマイグレーションの再実行
php artisan migrate:rollback --step=1
php artisan migrate
```

## 🔄 ロールバック手順

デプロイ失敗時の復旧：

```bash
# バックアップ一覧確認
ls -la /var/www/maji-kichi-backend.backup.*
ls -la /var/www/maji-kichi-frontend.backup.*

# ロールバック実行（タイムスタンプはバックアップのもの）
sudo rm -rf /var/www/maji-kichi-backend
sudo mv /var/www/maji-kichi-backend.backup.YYYYMMDD_HHMMSS /var/www/maji-kichi-backend

sudo rm -rf /var/www/maji-kichi-frontend
sudo mv /var/www/maji-kichi-frontend.backup.YYYYMMDD_HHMMSS /var/www/maji-kichi-frontend

# サービス再起動
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

## ✅ 動作確認チェックリスト

### フロントエンド
- [ ] `https://maji-kichi-meshi.takemitsu.net/` でSPAが表示される
- [ ] OAuthログインが正常に動作する
- [ ] 店舗一覧・レビュー機能が動作する

### 管理画面
- [ ] `https://maji-kichi-meshi.takemitsu.net/admin` でFilamentにアクセスできる
- [ ] 2FA設定が正常に動作する
- [ ] ダッシュボードが表示される
- [ ] 各リソース（Users, Shops, Reviews等）が操作できる

### API
- [ ] `https://maji-kichi-meshi.takemitsu.net/api/categories` でAPI応答がある
- [ ] JWT認証が正常に動作する

## 🔒 セキュリティ設定

### 必須対応
- [ ] 管理者は必ず2FA設定を完了する
- [ ] 強固なパスワードを設定する
- [ ] SSL証明書の有効期限を確認する（Let's Encrypt自動更新済み）

### 推奨対応
- 定期的なパスワード変更
- アクセスログの定期確認
- セキュリティアップデートの適用

## 📚 関連ドキュメント

- [CLAUDE.md](../CLAUDE.md) - プロジェクト全体設定
- [technical-specs.md](technical-specs.md) - 技術仕様・API仕様
- [database-er-diagram.md](database-er-diagram.md) - データベース設計
- [Laravel Filament公式ドキュメント](https://filamentphp.com/)

## 更新履歴

- 2025-10-17: deploy.sh中心の構成に再編成
- 2025-07-29: 初版作成（管理画面403エラー解決含む）
