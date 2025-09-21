#!/bin/bash

# ==============================================================================
# マイグレーションロールバックテストスクリプト
#
# 目的: 本番環境デプロイ前にマイグレーションのロールバック安全性を確認
#
# 使用方法:
#   bash scripts/test-migration-rollback.sh [--production]
#
# オプション:
#   --production  本番環境のデータベース設定を使用（要注意！）
# ==============================================================================

set -e  # エラー時に即座に終了

# カラー定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# スクリプト実行ディレクトリ
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$(dirname "${SCRIPT_DIR}")"

# 実行モード判定
if [ "$1" == "--production" ]; then
    echo -e "${RED}⚠️  WARNING: Running in PRODUCTION mode!${NC}"
    echo -e "${RED}This will affect your production database!${NC}"
    read -p "Are you absolutely sure? (type 'yes' to continue): " confirm
    if [ "$confirm" != "yes" ]; then
        echo "Aborted."
        exit 1
    fi
    ENV_MODE="production"
else
    ENV_MODE="development"
fi

cd "$BACKEND_DIR"

echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}   マイグレーションロールバックテスト開始${NC}"
echo -e "${BLUE}   Mode: ${ENV_MODE}${NC}"
echo -e "${BLUE}   Time: $(date '+%Y-%m-%d %H:%M:%S')${NC}"
echo -e "${BLUE}===============================================${NC}"

# ==============================================================================
# Step 1: データベースバックアップ
# ==============================================================================
echo -e "\n${YELLOW}Step 1: データベースバックアップ作成${NC}"

if [ "$ENV_MODE" == "development" ]; then
    # SQLiteの場合
    DB_FILE="database/database.sqlite"
    BACKUP_FILE="database/backup_$(date +%Y%m%d_%H%M%S).sqlite"

    if [ -f "$DB_FILE" ]; then
        cp "$DB_FILE" "$BACKUP_FILE"
        echo -e "${GREEN}✅ バックアップ作成完了: $BACKUP_FILE${NC}"
    else
        echo -e "${RED}❌ データベースファイルが見つかりません: $DB_FILE${NC}"
        exit 1
    fi
else
    # MySQLの場合（本番）
    echo "Creating MySQL backup..."
    mysqldump maji_kichi_meshi > "database/backup_$(date +%Y%m%d_%H%M%S).sql"
    echo -e "${GREEN}✅ MySQLバックアップ作成完了${NC}"
fi

# ==============================================================================
# Step 2: 現在のマイグレーション状態を記録
# ==============================================================================
echo -e "\n${YELLOW}Step 2: 現在のマイグレーション状態を記録${NC}"

php artisan migrate:status | grep 2025_09 > /tmp/migration_status_before.txt
cat /tmp/migration_status_before.txt

# データ件数を記録
echo -e "\n現在のデータ件数:"
php artisan tinker --execute="
    echo 'ReviewImages: ' . \App\Models\ReviewImage::count() . PHP_EOL;
    echo 'ShopImages: ' . \App\Models\ShopImage::count() . PHP_EOL;
"

# ==============================================================================
# Step 3: ロールバック実行
# ==============================================================================
echo -e "\n${YELLOW}Step 3: マイグレーションロールバック実行${NC}"

# 3つのマイグレーションをロールバック
echo "Rolling back 3 migrations..."
php artisan migrate:rollback --step=3 2>&1 | tee /tmp/rollback_output.txt

# ロールバック結果確認
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ ロールバック成功${NC}"
else
    echo -e "${RED}❌ ロールバック失敗${NC}"
    echo "エラー内容:"
    cat /tmp/rollback_output.txt

    # リカバリー手順を表示
    echo -e "\n${YELLOW}リカバリー手順:${NC}"
    echo "1. バックアップからデータベースを復元:"
    if [ "$ENV_MODE" == "development" ]; then
        echo "   cp $BACKUP_FILE $DB_FILE"
    else
        echo "   mysql maji_kichi_meshi < database/backup_*.sql"
    fi
    exit 1
fi

# ロールバック後の状態確認
echo -e "\n${BLUE}ロールバック後のマイグレーション状態:${NC}"
php artisan migrate:status | grep 2025_09

# ==============================================================================
# Step 4: テストデータ作成（旧構造）
# ==============================================================================
echo -e "\n${YELLOW}Step 4: 旧構造でテストデータ作成${NC}"

php artisan tinker --execute="
    // テスト用画像データ作成
    \$shopImage = new \App\Models\ShopImage();
    \$shopImage->shop_id = 1;
    \$shopImage->uuid = \Str::uuid();
    \$shopImage->filename = 'test-' . time() . '.jpg';
    \$shopImage->original_name = 'test.jpg';
    \$shopImage->mime_type = 'image/jpeg';
    \$shopImage->file_size = 1024;
    \$shopImage->image_sizes = json_encode([
        'thumbnail' => '/storage/images/shops/thumbnail/test.jpg',
        'small' => '/storage/images/shops/small/test.jpg',
        'medium' => '/storage/images/shops/medium/test.jpg',
        'large' => '/storage/images/shops/large/test.jpg',
    ]);
    \$shopImage->status = 'published';
    \$shopImage->save();

    echo 'テストデータ作成完了: ShopImage ID ' . \$shopImage->id . PHP_EOL;
"

# ==============================================================================
# Step 5: マイグレーション再適用
# ==============================================================================
echo -e "\n${YELLOW}Step 5: マイグレーション再適用${NC}"

php artisan migrate 2>&1 | tee /tmp/migrate_output.txt

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ マイグレーション再適用成功${NC}"
else
    echo -e "${RED}❌ マイグレーション再適用失敗${NC}"
    echo "エラー内容:"
    cat /tmp/migrate_output.txt
    exit 1
fi

# ==============================================================================
# Step 6: データ移行コマンド実行
# ==============================================================================
echo -e "\n${YELLOW}Step 6: データ移行コマンド実行${NC}"

# ドライラン実行
echo "Dry run first..."
php artisan shop-images:migrate-data --dry-run
php artisan images:fix-uuid --dry-run

# 実際の移行
echo -e "\n実際のデータ移行実行:"
php artisan shop-images:migrate-data
php artisan images:fix-uuid

# ==============================================================================
# Step 7: 結果検証
# ==============================================================================
echo -e "\n${YELLOW}Step 7: 結果検証${NC}"

php artisan tinker --execute="
    // データ整合性チェック
    \$shopImagesWithoutPath = \App\Models\ShopImage::whereNull('thumbnail_path')->count();
    \$reviewImagesWithoutUuid = \App\Models\ReviewImage::whereNull('uuid')->count();

    echo 'ShopImages without path: ' . \$shopImagesWithoutPath . PHP_EOL;
    echo 'ReviewImages without UUID: ' . \$reviewImagesWithoutUuid . PHP_EOL;

    if (\$shopImagesWithoutPath > 0 || \$reviewImagesWithoutUuid > 0) {
        echo '❌ データ移行に問題があります' . PHP_EOL;
        exit(1);
    } else {
        echo '✅ データ移行は正常に完了しました' . PHP_EOL;
    }
"

# ==============================================================================
# Step 8: 冪等性テスト
# ==============================================================================
echo -e "\n${YELLOW}Step 8: 冪等性テスト（コマンドの2回実行）${NC}"

echo "データ移行コマンドを再実行..."
php artisan shop-images:migrate-data
php artisan images:fix-uuid

echo -e "${GREEN}✅ 冪等性テスト完了${NC}"

# ==============================================================================
# テスト結果サマリー
# ==============================================================================
echo -e "\n${BLUE}===============================================${NC}"
echo -e "${BLUE}          テスト結果サマリー${NC}"
echo -e "${BLUE}===============================================${NC}"

echo -e "${GREEN}✅ ロールバック: 成功${NC}"
echo -e "${GREEN}✅ マイグレーション再適用: 成功${NC}"
echo -e "${GREEN}✅ データ移行: 成功${NC}"
echo -e "${GREEN}✅ 冪等性テスト: 成功${NC}"

echo -e "\n${BLUE}バックアップファイル:${NC}"
if [ "$ENV_MODE" == "development" ]; then
    echo "  $BACKUP_FILE"
else
    ls -la database/backup_*.sql | tail -1
fi

echo -e "\n${YELLOW}注意: テスト完了後は必要に応じてバックアップファイルを削除してください${NC}"
echo -e "${BLUE}===============================================${NC}"