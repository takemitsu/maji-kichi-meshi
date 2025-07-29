# マジキチメシアプリ 更新デプロイ手順書

## 📋 概要
マジキチメシアプリ（https://maji-kichi-meshi.takemitsu.net）の更新デプロイ手順書です。
2025年7月28日の初回デプロイ実績に基づいて作成されました。

## 🎯 対象環境
- **サーバー**: Sakura VPS (Ubuntu 24.04)
- **ドメイン**: maji-kichi-meshi.takemitsu.net
- **フロントエンド**: Vue.js + Nuxt.js 3.17.7 SPA
- **バックエンド**: Laravel 12.19.3 API + Laravel Filament
- **データベース**: MySQL 8.0
- **Webサーバー**: nginx 1.28.0 + PHP 8.3.23-FPM
- **SSL証明書**: Let's Encrypt（自動更新）

## 🚀 更新デプロイ手順

### Phase 1: 事前準備・環境確認

#### 1-1. サーバー接続とバックアップ
```bash
# SSH接続
ssh ubuntu@your-server-ip

# 現在の本番ファイルをバックアップ
sudo cp -r /var/www/maji-kichi-backend /var/www/maji-kichi-backend.backup.$(date +%Y%m%d_%H%M%S)
sudo cp -r /var/www/maji-kichi-frontend /var/www/maji-kichi-frontend.backup.$(date +%Y%m%d_%H%M%S)
```

#### 1-2. 環境確認
```bash
# 必要なサービス状態確認
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql

# Node.js バージョン確認（20.19.4 以上必要）
node --version

# PHP拡張確認
php -m | grep -E "(pdo|mysql|zip|mbstring|openssl)"
```

### Phase 2: ソースコード更新

#### 2-1. 最新コード取得
```bash
# 作業ディレクトリ移動
cd ~/deployment/maji-kichi-meshi

# 最新コードを取得
git pull origin main

# 変更確認
git log --oneline -5
```

#### 2-2. バックエンド依存関係更新
```bash
cd backend

# Composer依存関係更新
composer install --optimize-autoloader --no-dev

# データベースマイグレーション（新規マイグレーションがある場合）
php artisan migrate --force

# キャッシュクリア
php artisan config:clear
php artisan route:clear
php artisan view:clear

# テスト実行（重要：本番デプロイ前に必ず実行）
php artisan test
```

#### 2-3. フロントエンド依存関係更新・ビルド
```bash
cd ../frontend

# 依存関係更新（package.jsonに変更がある場合）
rm -rf node_modules package-lock.json
npm install

# .env設定確認
cat .env
# 以下の内容であることを確認:
# SITE_URL=https://maji-kichi-meshi.takemitsu.net
# API_BASE_URL=https://maji-kichi-meshi.takemitsu.net/api

# SPA ビルド実行
npm run generate

# ビルド結果確認
ls -la .output/public/
```

### Phase 3: 本番環境デプロイ

#### 3-1. メンテナンスモード開始（バックエンドのみ）
```bash
cd ~/deployment/maji-kichi-meshi/backend

# 一時的にメンテナンスモードを有効化（必要に応じて）
# php artisan down --message="System Update in Progress"
```

#### 3-2. バックエンドファイル更新
```bash
# バックエンドファイルを本番環境に配置
sudo cp -r ~/deployment/maji-kichi-meshi/backend/* /var/www/maji-kichi-backend/

# .env設定（変更がある場合のみ）
sudo cp ~/deployment/maji-kichi-meshi/backend/.env /var/www/maji-kichi-backend/

# 権限設定
sudo chown -R www-data:www-data /var/www/maji-kichi-backend
sudo chmod -R 755 /var/www/maji-kichi-backend
sudo chmod -R 775 /var/www/maji-kichi-backend/storage /var/www/maji-kichi-backend/bootstrap/cache

# 本番用キャッシュ再生成
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

#### 3-3. フロントエンドファイル更新
```bash
# フロントエンドファイルを本番環境に配置
sudo rm -rf /var/www/maji-kichi-frontend/*
sudo cp -r ~/deployment/maji-kichi-meshi/frontend/.output/public/* /var/www/maji-kichi-frontend/

# 権限設定
sudo chown -R www-data:www-data /var/www/maji-kichi-frontend
```

#### 3-4. サービス再起動・メンテナンスモード解除
```bash
# PHP-FPM再起動
sudo systemctl restart php8.3-fpm

# nginx設定テスト・再読み込み
sudo nginx -t
sudo systemctl reload nginx

# メンテナンスモード解除（使用した場合）
# cd /var/www/maji-kichi-backend
# sudo -u www-data php artisan up
```

### Phase 4: 動作確認

#### 4-1. サービス稼働確認
```bash
# nginx・PHP-FPM状態確認
sudo systemctl status nginx
sudo systemctl status php8.3-fpm

# ログ確認
sudo tail -f /var/log/nginx/error.log &
sudo tail -f /var/www/maji-kichi-backend/storage/logs/laravel-$(date +%Y-%m-%d).log &
```

#### 4-2. API動作確認
```bash
# APIエンドポイント確認
curl -I https://maji-kichi-meshi.takemitsu.net/api/categories
# 期待: HTTP/1.1 200 OK

# 管理画面確認
curl -I https://maji-kichi-meshi.takemitsu.net/admin/login
# 期待: HTTP/1.1 200 OK
```

#### 4-3. フロントエンド動作確認
```bash
# フロントエンド確認
curl -I https://maji-kichi-meshi.takemitsu.net/
# 期待: HTTP/1.1 200 OK

# SPA ルーティング確認
curl -I https://maji-kichi-meshi.takemitsu.net/shops
curl -I https://maji-kichi-meshi.takemitsu.net/reviews
curl -I https://maji-kichi-meshi.takemitsu.net/rankings
# すべて 200 OK が期待される
```

#### 4-4. ブラウザ動作確認
1. https://maji-kichi-meshi.takemitsu.net にアクセス
2. 主要機能の動作確認:
   - ログイン機能
   - 店舗一覧・詳細表示
   - レビュー機能
   - ランキング機能
3. 管理画面確認: https://maji-kichi-meshi.takemitsu.net/admin/login

## 🔄 ロールバック手順

問題が発生した場合のロールバック手順：

```bash
# バックアップから復旧
sudo rm -rf /var/www/maji-kichi-backend
sudo rm -rf /var/www/maji-kichi-frontend

# 最新のバックアップを確認
ls -la /var/www/maji-kichi-*.backup.*

# バックアップから復元
sudo mv /var/www/maji-kichi-backend.backup.YYYYMMDD_HHMMSS /var/www/maji-kichi-backend
sudo mv /var/www/maji-kichi-frontend.backup.YYYYMMDD_HHMMSS /var/www/maji-kichi-frontend

# サービス再起動
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

## ⚠️ 注意事項・トラブルシューティング

### 1. Node.js バージョンエラー
**症状**: `oxc-parser` native binding エラー
```bash
# 解決法: Node.js 20系にアップデート
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

### 2. PHP拡張不足エラー
**症状**: `ext-zip` required エラー
```bash
# 解決法: PHP拡張インストール
sudo apt install php8.3-zip
sudo systemctl restart php8.3-fpm
```

### 3. データベース接続エラー
```bash
# MySQL接続確認
php artisan tinker --execute="dd(DB::connection()->getPdo());"

# マイグレーション状態確認
php artisan migrate:status
```

### 4. 権限エラー
```bash
# storage/logs 権限修正
sudo chown -R www-data:www-data /var/www/maji-kichi-backend/storage
sudo chmod -R 775 /var/www/maji-kichi-backend/storage

# キャッシュ権限修正
sudo chown -R www-data:www-data /var/www/maji-kichi-backend/bootstrap/cache
sudo chmod -R 775 /var/www/maji-kichi-backend/bootstrap/cache
```

### 5. nginx 設定エラー
```bash
# nginx設定テスト
sudo nginx -t

# nginx エラーログ確認
sudo tail -f /var/log/nginx/error.log

# 設定ファイル確認
sudo cat /etc/nginx/conf.d/maji-kichi-meshi.conf
```

## 📚 本番環境情報

### ディレクトリ構成
```
/var/www/
├── maji-kichi-frontend/     # Vue.js SPA（静的ファイル）
├── maji-kichi-backend/      # Laravel API + Filament
├── html/                    # nginx default
└── ra8/                     # 既存アプリ（ra.takemitsu.net）
```

### nginx設定ファイル
```
/etc/nginx/conf.d/
├── maji-kichi-meshi.conf   # マジキチメシ設定
├── ra.conf                 # 既存アプリ設定
└── default.conf            # デフォルト設定
```

### SSL証明書
```
# 証明書確認
sudo certbot certificates

# 自動更新確認
sudo systemctl status certbot.timer
```

### データベース接続情報
```bash
# MySQL接続情報（.env）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maji_kichi_meshi
DB_USERNAME=maji_kichi_user
DB_PASSWORD=28bJYiDx2zUki1jd
```

### 管理者アカウント
- **Email**: takemitsu@notespace.jp
- **管理画面**: https://maji-kichi-meshi.takemitsu.net/admin/login

## 📝 更新履歴

### 2025-07-28 (初回デプロイ)
- マジキチメシアプリ初回リリース
- フロントエンド: Vue.js + Nuxt.js 3.17.7 SPA
- バックエンド: Laravel 12.19.3 + Filament
- SSL証明書: Let's Encrypt自動設定
- 全151テスト成功

---

**作成者**: Claude (AI Assistant)  
**作成日**: 2025-07-28  
**対象環境**: Sakura VPS + Ubuntu 24.04  
**次回更新時**: このファイルに更新履歴を追記してください