#!/bin/bash

# マジキチメシアプリ 自動デプロイスクリプト
# Usage: ./deploy.sh

set -e  # エラー時に停止

echo "🚀 マジキチメシアプリ デプロイ開始"

# 設定
DEPLOY_DIR="$HOME/deployment/maji-kichi-meshi"
BACKEND_PROD_DIR="/var/www/maji-kichi-backend"
FRONTEND_PROD_DIR="/var/www/maji-kichi-frontend"

# セキュリティチェック: パス検証
if [[ ! "$DEPLOY_DIR" =~ ^/home/.*/deployment/maji-kichi-meshi$ ]]; then
    echo "❌ DEPLOY_DIR path validation failed: $DEPLOY_DIR"
    exit 1
fi

if [[ ! "$BACKEND_PROD_DIR" =~ ^/var/www/maji-kichi-backend$ ]]; then
    echo "❌ BACKEND_PROD_DIR path validation failed: $BACKEND_PROD_DIR"
    exit 1
fi

if [[ ! "$FRONTEND_PROD_DIR" =~ ^/var/www/maji-kichi-frontend$ ]]; then
    echo "❌ FRONTEND_PROD_DIR path validation failed: $FRONTEND_PROD_DIR"
    exit 1
fi

# 色付きログ
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Phase 1: バックアップ
echo "📦 Phase 1: バックアップ作成"
BACKUP_TIMESTAMP=$(date +%Y%m%d_%H%M%S)

if [ -d "$BACKEND_PROD_DIR" ]; then
    sudo cp -r "$BACKEND_PROD_DIR" "${BACKEND_PROD_DIR}.backup.${BACKUP_TIMESTAMP}"
    log_info "バックエンドバックアップ作成: ${BACKEND_PROD_DIR}.backup.${BACKUP_TIMESTAMP}"
fi

if [ -d "$FRONTEND_PROD_DIR" ]; then
    sudo cp -r "$FRONTEND_PROD_DIR" "${FRONTEND_PROD_DIR}.backup.${BACKUP_TIMESTAMP}"
    log_info "フロントエンドバックアップ作成: ${FRONTEND_PROD_DIR}.backup.${BACKUP_TIMESTAMP}"
fi

# Phase 2: ソースコード更新
echo "📥 Phase 2: ソースコード更新"
cd "$DEPLOY_DIR"

log_info "git pull実行"
git pull origin main

# 変更確認
echo "📝 最新の変更:"
git log --oneline -3

# Phase 3: バックエンド更新
echo "🔧 Phase 3: バックエンド更新"
cd "$DEPLOY_DIR/backend"

# .env存在確認
if [ ! -f .env ]; then
    log_error ".envファイルが存在しません。手動で作成してください"
    echo "参考: .env.exampleをコピーして設定してください"
    exit 1
fi

log_info ".env設定ファイル確認完了"

# Composer依存関係更新
log_info "Composer依存関係更新"
composer install --optimize-autoloader --no-dev

# マイグレーション実行（新規がある場合）
log_info "データベースマイグレーション確認"
if php artisan migrate:status | grep -q "Pending"; then
    log_warn "新しいマイグレーションを実行"
    php artisan migrate --force
else
    log_info "マイグレーション: 更新なし"
fi

# テスト実行
log_info "テスト実行"
if vendor/bin/phpunit; then
    log_info "テスト: 全て成功"
else
    log_error "テスト失敗 - デプロイを中止"
    exit 1
fi

# Phase 4: フロントエンド更新  
echo "🎨 Phase 4: フロントエンド更新"
cd "$DEPLOY_DIR/frontend"

# .env存在確認
if [ ! -f .env ]; then
    log_error "フロントエンド.envファイルが存在しません。手動で作成してください"
    echo "参考: .env.exampleをコピーして設定してください"
    exit 1
fi

log_info "フロントエンド.env設定ファイル確認完了"

# package.jsonに変更があるかチェック
if git diff HEAD~1 package.json | grep -q .; then
    log_warn "package.json変更検出 - 依存関係再インストール"
    rm -rf node_modules package-lock.json
    npm install
else
    log_info "package.json: 変更なし"
fi

# SPAビルド
log_info "Nuxt.js SPAビルド実行"
npm run generate

# ビルド結果確認
if [ ! -f ".output/public/index.html" ]; then
    log_error "フロントエンドビルド失敗"
    exit 1
fi

# Phase 5: 本番環境デプロイ
echo "🌐 Phase 5: 本番環境デプロイ"

# バックエンドデプロイ
log_info "バックエンドファイル配置"
sudo cp -r "$DEPLOY_DIR/backend/"* "$BACKEND_PROD_DIR/"
sudo cp "$DEPLOY_DIR/backend/.env" "$BACKEND_PROD_DIR/"

# 権限設定
sudo chown -R www-data:www-data "$BACKEND_PROD_DIR"
sudo chmod -R 755 "$BACKEND_PROD_DIR"
sudo chmod -R 775 "$BACKEND_PROD_DIR/storage" "$BACKEND_PROD_DIR/bootstrap/cache"

# 本番用キャッシュ生成
log_info "Laravel本番用キャッシュ生成"
cd "$BACKEND_PROD_DIR"
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache  
sudo -u www-data php artisan view:cache

# フロントエンドデプロイ
log_info "フロントエンドファイル配置"

# 安全な削除: ディレクトリ存在確認
if [ -d "$FRONTEND_PROD_DIR" ] && [ "$(ls -A "$FRONTEND_PROD_DIR" 2>/dev/null)" ]; then
    sudo rm -rf "$FRONTEND_PROD_DIR"/*
fi

sudo cp -r "$DEPLOY_DIR/frontend/.output/public/"* "$FRONTEND_PROD_DIR/"
sudo chown -R www-data:www-data "$FRONTEND_PROD_DIR"

# Phase 6: サービス再起動
echo "🔄 Phase 6: サービス再起動"
sudo systemctl restart php8.3-fpm
sudo nginx -t
sudo systemctl reload nginx

log_info "PHP-FPM・nginx再起動完了"

# Phase 7: 動作確認
echo "🔍 Phase 7: 動作確認"

# APIテスト
if curl -f -s -I "https://maji-kichi-meshi.takemitsu.net/api/categories" > /dev/null; then
    log_info "API: 正常"
else
    log_error "API: エラー"
fi

# フロントエンドテスト
if curl -f -s -I "https://maji-kichi-meshi.takemitsu.net/" > /dev/null; then
    log_info "フロントエンド: 正常"
else
    log_error "フロントエンド: エラー"
fi

# 管理画面テスト
if curl -f -s -I "https://maji-kichi-meshi.takemitsu.net/admin/login" > /dev/null; then
    log_info "管理画面: 正常"
else
    log_error "管理画面: エラー"  
fi

# 完了
echo "🎉 デプロイ完了!"
echo "📱 アプリURL: https://maji-kichi-meshi.takemitsu.net"
echo "🔧 管理画面: https://maji-kichi-meshi.takemitsu.net/admin/login"
echo "📦 バックアップ: ${BACKUP_TIMESTAMP}"

# ログ確認案内
echo ""
echo "📋 ログ確認:"
echo "sudo tail -f /var/log/nginx/error.log"
echo "sudo tail -f $BACKEND_PROD_DIR/storage/logs/laravel-\$(date +%Y-%m-%d).log"