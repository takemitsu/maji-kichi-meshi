# 画像遅延生成機能 リリースプラン

**作成日**: 2025-09-21
**バージョン**: 1.0.0

## 📦 必要なコンポーネント

### マイグレーション（3ファイル - deploy.shで自動実行）
1. `2025_09_19_120040_unify_image_tables_structure.php`
2. `2025_09_20_143715_drop_status_column_from_shop_images_table.php`
3. `2025_09_20_154503_add_uuid_to_review_images_table.php`

### データ移行コマンド（2つ - 手動実行必要）
```bash
php artisan shop-images:migrate-data  # ShopImage既存データ移行
php artisan images:fix-uuid           # UUID統一化
```

## 🔧 デプロイ手順

### 1. VPSでのデプロイ

```bash
# SSHでVPSにログイン
ssh sakura-vps

# データベースバックアップ（重要）
mysqldump -u maji_kichi_user -p maji_kichi_meshi > ~/backup_db_$(date +%Y%m%d_%H%M%S).sql

# 画像ファイルバックアップ（fix-uuidコマンドがファイルをリネームするため必須）
tar -czf ~/backup_images_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/maji-kichi-backend/storage/app/public/images/

# ⚠️ 重要: largeディレクトリをoriginalにリネーム（必須）
# 既存環境にはoriginalが存在しないため、largeをoriginalとして使用
cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/large shops/original
sudo mv reviews/large reviews/original
# 権限確認
sudo chown -R www-data:www-data shops/original reviews/original

# デプロイディレクトリへ移動
cd ~/deployment/maji-kichi-meshi/

# 最新コード取得
git pull origin main

# デプロイスクリプト実行（マイグレーション自動実行）
./deploy.sh

# データ移行コマンド実行（本番環境で）
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan shop-images:migrate-data
sudo -u www-data php artisan images:fix-uuid
```

## ✅ 動作確認

```bash
# API確認
curl https://maji-kichi-meshi.takemitsu.net/api/reviews/1
# → images配列にurls確認

# 画像配信確認
curl -I https://maji-kichi-meshi.takemitsu.net/api/images/reviews/thumbnail/[filename]
```

## 🔄 ロールバック

```bash
# マイグレーションロールバック
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan migrate:rollback --step=3

# データベースリストア（必要な場合）
mysql -u maji_kichi_user -p maji_kichi_meshi < ~/backup_db_[timestamp].sql
```

## ⚠️ 注意事項
- **必須**: `large`ディレクトリを`original`にリネームしないと遅延生成が機能しません
- データ移行コマンドは冪等性保証（何度実行しても安全）
- 既存ファイルは保護される
- originalディレクトリがないと新サイズ生成ができません