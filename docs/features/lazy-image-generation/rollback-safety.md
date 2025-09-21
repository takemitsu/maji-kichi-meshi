# マイグレーションロールバック安全性ガイド

**作成日**: 2025-09-21
**更新日**: 2025-09-21
**目的**: 画像遅延生成機能マイグレーションの安全なロールバック手順

## 🔴 発見された問題と対策

### 1. NOT NULL制約によるロールバック失敗

**問題**:
- `review_images.large_path`をNULLからNOT NULLに変更しようとしてロールバック失敗
- 既存データにNULL値が存在する場合、制約違反エラー

**対策実施済み**:
```php
// 2025_09_19_120040_unify_image_tables_structure.php
// large_pathはnullableのまま保持（既存のNULL値がある可能性があるため）
// $table->string('large_path')->nullable(false)->change(); // コメントアウト
```

### 2. インデックス重複エラー

**問題**:
- `shop_images_shop_id_index`が既に存在する状態で再作成しようとしてエラー
- マイグレーション再実行時の冪等性が保証されない

**対策実施済み**:
```php
// 2025_09_20_143715_drop_status_column_from_shop_images_table.php
if (!Schema::hasIndex('shop_images', $indexName)) {
    $table->index('shop_id', $indexName);
}
```

### 3. データ移行コマンドの前提条件不足

**問題**:
- 必要なカラムが存在しない状態でコマンドを実行すると全件失敗
- エラーハンドリングが不十分

**対策実施済み**:
```php
// MigrateShopImageData.php, FixImageUuidAndFiles.php
// 必要なカラムの存在確認を追加
if (!\Schema::hasColumn('shop_images', $column)) {
    $this->error("Required column missing: {$column}");
    return Command::FAILURE;
}
```

## 📋 安全なロールバック手順

### 1. 事前準備

```bash
# データベースバックアップ（必須）
php artisan backup:run --only-db

# 現在の状態を記録
php artisan migrate:status > migration_status_before.txt
php artisan tinker --execute="echo 'Images: ' . \App\Models\ReviewImage::count();"
```

### 2. ロールバック実行

```bash
# 画像遅延生成関連の3つのマイグレーションをロールバック
php artisan migrate:rollback --step=3

# ロールバック確認
php artisan migrate:status | grep 2025_09
```

### 3. トラブルシューティング

#### ロールバック失敗時の対処

**ケース1: NOT NULL制約エラー**
```sql
-- 手動でNULL値を許可
ALTER TABLE review_images MODIFY large_path VARCHAR(255) NULL;
```

**ケース2: インデックス重複エラー**
```sql
-- 既存インデックスを削除
DROP INDEX shop_images_shop_id_index ON shop_images;
```

**ケース3: カラムが既に削除されている**
```bash
# エラーを無視して次のステップへ進む
php artisan migrate:rollback --step=1 --force
```

### 4. データベース復元（最終手段）

```bash
# SQLiteの場合
cp database/backup_*.sqlite database/database.sqlite

# MySQLの場合
mysql -u username -p database_name < backup_*.sql
```

## 🚀 本番環境でのデプロイ手順

### 1. デプロイ前チェックリスト

- [ ] 開発環境でロールバックテスト完了
- [ ] データベースバックアップ取得
- [ ] メンテナンスモード準備
- [ ] ロールバック手順書確認

### 2. デプロイ実行

```bash
# 1. メンテナンスモード開始
php artisan down --render="errors::503" --retry=60

# 2. ⚠️ 重要: largeディレクトリをoriginalにリネーム（必須）
cd storage/app/public/images/
mv shops/large shops/original
mv reviews/large reviews/original
# ※これを忘れると遅延生成が機能しません！

# 3. コード更新
git pull origin main

# 4. マイグレーション実行（一度にすべて実行）
php artisan migrate --force

# 5. データ移行コマンド実行
php artisan shop-images:migrate-data
php artisan images:fix-uuid

# 6. キャッシュクリア
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# 7. メンテナンスモード解除
php artisan up
```

### 3. デプロイ後の確認

```bash
# データ整合性チェック
php artisan tinker --execute="
    \$incomplete = \App\Models\ShopImage::whereNull('thumbnail_path')->count();
    echo 'Incomplete migrations: ' . \$incomplete;
"

# エラーログ確認
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

## ⚠️ 重要な注意事項

### してはいけないこと

1. **部分的なマイグレーション実行**
   - ❌ 1つずつマイグレーションを実行
   - ✅ すべてのマイグレーションを一度に実行

2. **データ移行前のロールバック**
   - ❌ データ移行コマンド実行後にロールバック
   - ✅ ロールバックする場合は必ずバックアップから復元

3. **本番環境での未検証コマンド実行**
   - ❌ 開発環境でテストせずに本番実行
   - ✅ 必ず開発環境で全手順を検証

### ベストプラクティス

1. **ロールバックは最終手段**
   - 基本的に前進のみ（forward-only）
   - 問題があれば修正マイグレーションを追加

2. **段階的デプロイ**
   - ステージング環境で十分にテスト
   - カナリアデプロイメントの検討

3. **監視とアラート**
   - デプロイ直後は重点的に監視
   - エラー率の急増を即座に検知

## 🛠️ テストツール

### 自動テストスクリプト

```bash
# ロールバックテスト実行
bash backend/scripts/test-migration-rollback.sh

# 本番環境シミュレーション（要注意）
bash backend/scripts/test-migration-rollback.sh --production
```

### 手動検証コマンド

```bash
# マイグレーション状態確認
php artisan migrate:status

# データ整合性確認
php artisan tinker --execute="
    print_r([
        'ShopImages' => \App\Models\ShopImage::count(),
        'ReviewImages' => \App\Models\ReviewImage::count(),
        'WithoutPath' => \App\Models\ShopImage::whereNull('thumbnail_path')->count(),
        'WithoutUUID' => \App\Models\ReviewImage::whereNull('uuid')->count(),
    ]);
"
```

## 📝 トラブルシューティングログ

### 2025-09-21: パターンAテスト

**問題**: ロールバック時にNOT NULL制約エラー
**原因**: `large_path`にNULL値が存在
**解決**: マイグレーションファイルを修正してNULL許可を維持

**問題**: インデックス重複エラー
**原因**: `shop_images_shop_id_index`が既に存在
**解決**: インデックス存在確認を追加

**問題**: データ移行コマンド失敗
**原因**: 必要なカラムが存在しない
**解決**: カラム存在確認を追加

## 🔗 関連ドキュメント

- [実装計画](./01-implementation-plan.md)
- [リハーサル計画](./rehearsal-plan.md)
- [リリース計画](./release-plan.md)
- [進捗管理](./progress.md)

---

**重要**: このドキュメントは画像遅延生成機能のマイグレーションに特化しています。
他のマイグレーションには適用しないでください。