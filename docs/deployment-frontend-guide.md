# フロントエンド本番リリース手順書（Sakura VPS + nginx）

## 📋 概要
Nuxt.js SPAアプリケーションをSakura VPS + nginxで本番リリースする手順書です。

## 🎯 前提条件
- Sakura VPS（既存環境）
- nginx（既存設定）
- Node.js v18以上
- Git（ソースコード取得用）

## 📂 アーキテクチャ
```
┌─────────────────────┐    ┌─────────────────────┐
│   ユーザーブラウザ    │────│   nginx (80/443)   │
└─────────────────────┘    └─────────────────────┘
                                      │
                           ┌─────────────────────┐
                           │  静的ファイル配信    │
                           │ /var/www/frontend/  │
                           └─────────────────────┘
                                      │
                           ┌─────────────────────┐
                           │ Laravel API Backend │
                           │   (proxy_pass)     │
                           └─────────────────────┘
```

## 🚀 デプロイ手順

### Phase 1: サーバー準備

#### 1-1. Node.js環境確認
```bash
# SSH接続後
node --version  # v18以上確認
npm --version   # npm確認

# Node.js未導入の場合
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

#### 1-2. ディレクトリ準備
```bash
# フロントエンド用ディレクトリ作成
sudo mkdir -p /var/www/frontend
sudo chown $USER:$USER /var/www/frontend

# 作業ディレクトリ作成
mkdir -p ~/deployment
cd ~/deployment
```

### Phase 2: ソースコード取得・ビルド

#### 2-1. リポジトリクローン
```bash
# Git リポジトリクローン
git clone [リポジトリURL] maji-kichi-meshi
cd maji-kichi-meshi/frontend

# または既存リポジトリ更新
cd ~/deployment/maji-kichi-meshi
git pull origin main
cd frontend
```

#### 2-2. 依存関係インストール
```bash
# npm dependencies インストール
npm ci --production=false

# 型チェック実行（エラー確認）
npm run type-check

# Lint チェック実行（コード品質確認）
npm run lint:fix
```

#### 2-3. 環境変数設定
```bash
# .env.example から .env ファイルを作成
cp .env.example .env

# 本番環境用の設定に編集
vim .env

# 以下のように設定:
```

```bash
# SEO・環境設定用環境変数
# 本番環境では実際のドメインを設定してください

# サイトURL（SEOメタデータ・canonical URL用）
SITE_URL=https://your-domain.com

# API エンドポイント
API_BASE_URL=https://your-domain.com/api
```

```bash
# 設定確認
cat .env
```

#### 2-4. SPA ビルド実行
```bash
# Nuxt.js SPA ビルド（重要：generateコマンド）
npm run generate

# ビルド結果確認
ls -la .output/public/
# 以下のファイルが生成されているか確認:
# - index.html
# - _nuxt/ ディレクトリ
# - favicon.ico
# - robots.txt
```

### Phase 3: nginx設定

#### 3-1. nginx設定ファイル作成
```bash
# nginx設定ファイル作成（Ubuntu環境のconf.d使用）
sudo vim /etc/nginx/conf.d/maji-kichi-meshi.conf

# 以下の内容を設定
```

```nginx
# 実環境例: takemitsu.netドメインを使用した統合設定
server {
    listen 80;
    listen [::]:80;
    server_name takemitsu.net www.takemitsu.net;
    
    # フロントエンド静的ファイル配信
    root /var/www/frontend;
    index index.html;
    
    # SPA用設定: すべてのルートをindex.htmlにフォールバック
    location / {
        try_files $uri $uri/ /index.html;
        
        # キャッシュ設定
        add_header Cache-Control "public, no-cache, must-revalidate";
    }
    
    # 静的アセット用キャッシュ設定
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }
    
    # API リクエストをバックエンドへプロキシ
    location /api {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # CORS対応
        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
        add_header Access-Control-Allow-Headers "Origin, Content-Type, Accept, Authorization";
        
        # Preflight リクエスト対応
        if ($request_method = 'OPTIONS') {
            return 204;
        }
    }
    
    # セキュリティヘッダー
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # gzip圧縮
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
```

#### 3-2. SSL証明書設定（Let's Encrypt）
```bash
# Certbot インストール（未導入の場合）
sudo apt update
sudo apt install certbot python3-certbot-nginx

# SSL証明書取得
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 自動更新設定確認
sudo systemctl status certbot.timer
```

#### 3-3. nginx設定有効化
```bash
# conf.d配置済みなので有効化は不要

# nginx設定テスト
sudo nginx -t

# nginx再読み込み
sudo systemctl reload nginx
```

### Phase 4: 静的ファイルデプロイ

#### 4-1. ビルドファイル配置
```bash
# 既存ファイルのバックアップ（初回以降）
if [ -d "/var/www/frontend" ]; then
    sudo mv /var/www/frontend /var/www/frontend.backup.$(date +%Y%m%d_%H%M%S)
fi

# 新しいビルドファイルを配置
sudo cp -r ~/deployment/maji-kichi-meshi/frontend/.output/public/* /var/www/frontend/

# 権限設定
sudo chown -R www-data:www-data /var/www/frontend
sudo chmod -R 644 /var/www/frontend
sudo find /var/www/frontend -type d -exec chmod 755 {} \;
```

#### 4-2. デプロイ確認
```bash
# ファイル配置確認
ls -la /var/www/frontend/
# 期待ファイル:
# - index.html
# - _nuxt/ (JSバンドルファイル)
# - favicon.ico
# - robots.txt

# nginx設定確認
sudo nginx -t

# nginx ステータス確認
sudo systemctl status nginx
```

## ✅ 動作確認

### 1. 基本動作確認
```bash
# ローカル確認
curl -I http://localhost/
# 期待: HTTP/1.1 200 OK

# 外部確認
curl -I https://your-domain.com/
# 期待: HTTP/1.1 200 OK
```

### 2. SPA ルーティング確認
```bash
# 各ルートの確認
curl -I https://your-domain.com/shops
curl -I https://your-domain.com/reviews
curl -I https://your-domain.com/rankings
# すべて 200 OK が期待される（SPAフォールバック）
```

### 3. API プロキシ確認
```bash
# API エンドポイント確認
curl https://your-domain.com/api/shops
# JSON レスポンス確認
```

### 4. ブラウザ確認
1. `https://your-domain.com` にアクセス
2. SPAアプリが正常に表示されることを確認
3. ページ遷移がクライアントサイドで動作することを確認
4. API呼び出しが正常に動作することを確認

## 🔄 継続デプロイメント

### 更新手順（新機能デプロイ時）
```bash
#!/bin/bash
# deploy.sh - 更新用スクリプト

set -e

echo "🚀 フロントエンド更新開始"

# 1. 最新コード取得
cd ~/deployment/maji-kichi-meshi
git pull origin main

# 2. 依存関係更新
cd frontend
npm ci

# 3. 型チェック・Lint
npm run type-check
npm run lint

# 4. ビルド実行
npm run generate

# 5. 現在のファイルをバックアップ
sudo mv /var/www/frontend /var/www/frontend.backup.$(date +%Y%m%d_%H%M%S)

# 6. 新しいファイルをデプロイ
sudo cp -r .output/public/* /var/www/frontend/
sudo chown -R www-data:www-data /var/www/frontend
sudo chmod -R 644 /var/www/frontend
sudo find /var/www/frontend -type d -exec chmod 755 {} \;

# 7. nginx再読み込み
sudo nginx -t
sudo systemctl reload nginx

echo "✅ フロントエンド更新完了"
```

### ロールバック手順
```bash
# 最新のバックアップを確認
ls -la /var/www/frontend.backup.*

# ロールバック実行
sudo rm -rf /var/www/frontend
sudo mv /var/www/frontend.backup.YYYYMMDD_HHMMSS /var/www/frontend
sudo systemctl reload nginx
```

## ⚠️ 注意事項・よくある問題

### 1. ビルドエラー対策
```bash
# Node.js メモリ不足の場合
export NODE_OPTIONS="--max-old-space-size=4096"
npm run generate

# TypeScript エラーがある場合
npm run type-check
# エラー修正後再実行
```

### 2. nginx 設定トラブル
```bash
# nginx 設定エラー確認
sudo nginx -t

# nginx エラーログ確認
sudo tail -f /var/log/nginx/error.log

# アクセスログ確認
sudo tail -f /var/log/nginx/access.log
```

### 3. SPA ルーティング問題
- **症状**: 直接URLアクセス時に404エラー
- **原因**: `try_files $uri $uri/ /index.html;` 設定不備
- **対策**: nginx設定を再確認

### 4. API 接続問題
- **症状**: API呼び出しでCORSエラー
- **原因**: CORS設定またはプロキシ設定不備
- **対策**: nginx設定のlocation /api セクションを確認

### 5. キャッシュ問題
- **症状**: 更新後も古いファイルが表示される
- **対策**: ブラウザのハードリフレッシュ（Ctrl+Shift+R）

## 📚 参考情報

### 必要な追加情報
1. **ドメイン名**: your-domain.com の実際のドメイン
2. **SSL証明書**: Let's Encryptまたは既存証明書の設定
3. **バックエンドURL**: 実際のLaravel APIのURL
4. **Git リポジトリURL**: 実際のリポジトリアドレス

### Nuxt.js SPA特有の設定
- `ssr: false` : SPA モード有効
- `npm run generate` : 静的ファイル生成
- `try_files` : SPA ルーティング対応

### 性能・セキュリティ
- gzip圧縮有効
- 静的アセットキャッシュ設定
- セキュリティヘッダー設定
- CORS対応

---

# バックエンド本番リリース手順書（Laravel + PHP-FPM）

## 📋 バックエンド概要
Laravel API + PHP-FPM + nginx構成での本番デプロイ手順です。

## 🎯 バックエンド前提条件
- Sakura VPS（既存環境）
- nginx 1.28.0
- PHP 8.2以上 + PHP-FPM
- PostgreSQL または MySQL
- Composer

## 📂 バックエンドアーキテクチャ
```
┌─────────────────────┐    ┌─────────────────────┐
│   nginx (80/443)   │────│    PHP-FPM          │
└─────────────────────┘    └─────────────────────┘
                                      │
                           ┌─────────────────────┐
                           │   Laravel API       │
                           │   /var/www/api/     │
                           └─────────────────────┘
                                      │
                           ┌─────────────────────┐
                           │  PostgreSQL/MySQL   │
                           │   Database          │
                           └─────────────────────┘
```

## 🚀 バックエンドデプロイ手順

### Phase B1: サーバー準備

#### B1-1. PHP環境確認
```bash
# PHP バージョン確認
php --version  # PHP 8.2以上確認
php-fpm8.3 --version

# 必要な拡張確認
php -m | grep -E "(pdo|mbstring|openssl|tokenizer|bcmath|ctype|json|xml)"

# Composer確認
composer --version
```

#### B1-2. バックエンドディレクトリ準備
```bash
# Laravel API用ディレクトリ作成
sudo mkdir -p /var/www/api
sudo chown $USER:$USER /var/www/api

# ログディレクトリ権限設定
sudo mkdir -p /var/log/php
sudo chown www-data:www-data /var/log/php
```

### Phase B2: Laravel API デプロイ

#### B2-1. ソースコード配置
```bash
# バックエンドソースコード配置
cd ~/deployment/maji-kichi-meshi/backend

# 本番用環境変数設定
sudo cp .env.example .env.production
sudo vim .env.production

# 以下の項目を本番環境に合わせて設定:
```

```bash
APP_NAME="マジキチメシ"
APP_ENV=production
APP_KEY=  # php artisan key:generate で生成
APP_DEBUG=false
APP_URL=https://your-domain.com

# ログ設定（日別ローテーション）
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=info

# データベース設定
DB_CONNECTION=pgsql  # または mysql
DB_HOST=127.0.0.1
DB_PORT=5432  # PostgreSQL の場合
DB_DATABASE=maji_kichi_meshi
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# JWT設定
JWT_SECRET=  # 64文字のランダム文字列
JWT_TTL=10080  # 1週間(分)

# OAuth設定（Google専用）
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# フロントエンドURL
FRONTEND_URL=https://your-domain.com

# セッション設定
SESSION_DRIVER=database
SESSION_LIFETIME=120

# キャッシュ設定
CACHE_STORE=database

# キュー設定
QUEUE_CONNECTION=database
```

#### B2-2. 依存関係とキー生成
```bash
# Composer dependencies（本番用）
# 注意: filament:upgrade が自動実行されます
composer install --optimize-autoloader --no-dev

# Laravel キー生成
php artisan key:generate

# JWT シークレット生成
php artisan jwt:secret
```

#### B2-3. データベースセットアップ
```bash
# マイグレーション実行
php artisan migrate --force

# 基本データ投入（カテゴリマスタ）
php artisan db:seed --class=CategorySeeder

# 管理者ユーザー作成（対話式）
php artisan admin:create
# Email address: admin@your-domain.com
# Full name: System Admin  
# Password: [強固なパスワードを入力]
# Confirm password: [同じパスワードを再入力]
# Select role: admin

# ファイルシステム権限設定
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### B2-4. 設定キャッシュ最適化
```bash
# 本番用設定キャッシュ
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 設定確認
php artisan about
```

### Phase B3: nginx + PHP-FPM設定

#### B3-1. PHP-FPM設定確認
```bash
# 現在の設定確認が必要です
# PHP-FPM設定ファイル確認
sudo cat /etc/php/8.3/fpm/pool.d/www.conf | head -20

# PHP-FPM サービス確認
sudo systemctl status php8.3-fpm
```

#### B3-2. Laravel用nginx設定追加
既存のnginx設定に以下を追加:

```nginx
# /etc/nginx/conf.d/maji-kichi-meshi.conf を新規作成
# または既存のdefault.confを修正

# Laravel API バックエンド設定
server {
    listen 80;
    server_name api.your-domain.com;  # APIサブドメイン用（オプション）
    root /var/www/api/public;
    index index.php;

    # Laravel API 用設定
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM 処理
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Laravel環境変数
        fastcgi_param APP_ENV production;
        
        # セキュリティヘッダー（バックエンドから自動付与済み）
        # SecurityHeadersMiddleware が処理
    }

    # 静的ファイル処理無効化（API専用）
    location ~ /\. {
        deny all;
    }
    
    # Laravel storage/logs へのアクセス拒否
    location ~ ^/(storage|bootstrap)/.*$ {
        deny all;
    }
}

# または、既存設定に API location ブロックを修正:
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    
    # フロントエンド（既存）
    root /var/www/frontend;
    index index.html;
    
    # API リクエストを Laravel へ
    location /api {
        alias /var/www/api/public;
        try_files $uri $uri/ @laravel;
        
        location ~ ^/api/(.*)\.php$ {
            alias /var/www/api/public;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME /var/www/api/public/index.php;
            include fastcgi_params;
            fastcgi_param PATH_INFO /$1;
        }
    }
    
    location @laravel {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/api/public/index.php;
        include fastcgi_params;
    }
    
    # フロントエンド設定（既存のまま）
    location / {
        try_files $uri $uri/ /index.html;
        add_header Cache-Control "public, no-cache, must-revalidate";
    }
}
```

#### B3-3. ファイル配置と権限設定
```bash
# Laravel ファイル配置
sudo cp -r ~/deployment/maji-kichi-meshi/backend/* /var/www/api/

# .env ファイル配置
sudo cp /var/www/api/.env.production /var/www/api/.env

# 権限設定
sudo chown -R www-data:www-data /var/www/api
sudo chmod -R 755 /var/www/api
sudo chmod -R 775 /var/www/api/storage /var/www/api/bootstrap/cache

# nginx 設定テスト
sudo nginx -t

# サービス再起動
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm
```

### Phase B4: バックエンド動作確認

#### B4-1. Laravel API テスト
```bash
# ヘルスチェック
curl -I http://localhost/api/categories
# 期待: HTTP/1.1 200 OK + セキュリティヘッダー

# Laravel ログ確認
sudo tail -f /var/www/api/storage/logs/laravel-$(date +%Y-%m-%d).log

# PHP-FPM ログ確認
sudo tail -f /var/log/php8.3-fpm.log
```

#### B4-2. 管理画面確認（Laravel Filament）
```bash
# Filament 管理画面アクセス
curl -I https://your-domain.com/admin/login

# 管理者ユーザー確認
php artisan tinker
>>> App\Models\User::where('role', 'admin')->first();
```

## 🔒 セキュリティ設定（実装済み）

### 自動適用されるセキュリティ機能
- ✅ **セキュリティヘッダー**: X-Frame-Options, X-XSS-Protection等
- ✅ **セキュリティログ**: 認証失敗、権限エラー、攻撃検知
- ✅ **レート制限**: ユーザーベース制限（reviews: 5/h, images: 20/h等）
- ✅ **日別ログローテーション**: storage/logs/laravel-YYYY-MM-DD.log

### 追加設定推奨
```bash
# PHP設定強化（/etc/php/8.3/fpm/php.ini）
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# nginx設定強化
server_tokens off;  # nginx バージョン非表示
```

## 🔄 バックエンド継続デプロイメント

### バックエンド更新手順
```bash
#!/bin/bash
# backend-deploy.sh

set -e

echo "🚀 バックエンド更新開始"

# 1. メンテナンスモード
cd /var/www/api
sudo -u www-data php artisan down

# 2. 最新コード取得
cd ~/deployment/maji-kichi-meshi
git pull origin main

# 3. 依存関係更新
cd backend
composer install --optimize-autoloader --no-dev

# 4. 設定反映
sudo cp backend/* /var/www/api/ -r
sudo chown -R www-data:www-data /var/www/api

# 5. データベースマイグレーション
cd /var/www/api
sudo -u www-data php artisan migrate --force

# 6. キャッシュクリア&再構築
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 7. サービス再起動
sudo systemctl reload php8.3-fpm

# 8. メンテナンスモード解除
sudo -u www-data php artisan up

echo "✅ バックエンド更新完了"
```

## ⚠️ バックエンド注意事項

### 1. 権限問題
```bash
# ログ書き込みエラーの場合
sudo chown -R www-data:www-data /var/www/api/storage
sudo chmod -R 775 /var/www/api/storage

# キャッシュエラーの場合
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
```

### 2. データベース接続エラー
```bash
# 接続確認
php artisan tinker
>>> DB::connection()->getPdo();

# マイグレーション確認
php artisan migrate:status
```

### 3. JWT認証エラー
```bash
# JWT秘密鍵再生成（注意：既存トークン無効化）
php artisan jwt:secret --force

# キー確認
php artisan tinker
>>> config('jwt.secret');
```

### 4. セキュリティログ監視
```bash
# 攻撃検知ログ確認
sudo grep -E "(Authentication failed|Rate limit exceeded|Suspicious activity)" /var/www/api/storage/logs/laravel-*.log

# セキュリティアラート監視
sudo tail -f /var/www/api/storage/logs/laravel-$(date +%Y-%m-%d).log | grep -E "(ALERT|WARNING|ERROR)"
```

---

**追記者**: バックエンド担当Claude  
**追記日**: 2025-07-10  
**対象**: Laravel 12.19.3 + PHP 8.2以上 + PHP-FPM  
**作成者**: フロントエンド担当Claude  
**作成日**: 2025-07-10  
**対象**: Nuxt.js 3.17.6 + SPA モード  
**環境**: Sakura VPS + nginx 1.28.0