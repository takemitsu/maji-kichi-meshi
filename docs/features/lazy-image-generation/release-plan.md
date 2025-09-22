# 画像遅延生成機能 本番リリース完全ガイド

**作成日**: 2025-09-21
**更新日**: 2025-09-22
**バージョン**: 2.0.0（統合版）

---

## 📦 必要なコンポーネント

### マイグレーションファイル（3ファイル - deploy.shで自動実行）
1. `2025_09_19_120040_unify_image_tables_structure.php`
2. `2025_09_20_143715_drop_status_column_from_shop_images_table.php`
3. `2025_09_20_154503_add_uuid_to_review_images_table.php`

### データ移行コマンド（2つ - 手動実行必要）
```bash
php artisan shop-images:migrate-data  # ShopImage既存データ移行
php artisan images:fix-uuid           # UUID統一化
```

---

## 🚨 最重要事項（これを忘れると遅延生成が動作しません）

### ⚠️ largeディレクトリのリネーム（絶対に忘れない）

```bash
# 本番環境ではlargeディレクトリが存在し、originalは存在しない
# largeをoriginalにリネームする必要がある（超重要）

cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/large shops/original      # ← これ忘れると動かない！
sudo mv reviews/large reviews/original   # ← これ忘れると動かない！

# 権限確認
sudo chown -R www-data:www-data shops/original reviews/original
```

**なぜ必要？**
- 新システムは`original`ディレクトリから各サイズを生成
- 本番環境には`large`しか存在しない
- リネームしないと元画像が見つからずエラーになる

---

## 📋 事前準備チェックリスト

### 1. ローカル環境での確認
- [ ] パターンAテスト（完全シミュレーション）実施済み
- [ ] ロールバック→再マイグレーションのテスト成功
- [ ] データ移行コマンドの冪等性確認（3回実行してエラーなし）

### 2. バックアップ準備
- [ ] データベースバックアップコマンド準備
- [ ] 画像ファイルバックアップコマンド準備
- [ ] バックアップ保存先の空き容量確認

### 3. 開発環境でのテスト
- [ ] ロールバックテスト実行: `bash backend/scripts/test-migration-rollback.sh`
- [ ] メンテナンスモード準備確認
- [ ] ロールバック手順書確認

---

## 🚀 本番デプロイ実行手順

### Phase 1: 事前準備とバックアップ
```bash
# SSHログイン
ssh sakura-vps

# バックアップ作成（必須）
mysqldump -u maji_kichi_user -p maji_kichi_meshi > ~/backup_db_$(date +%Y%m%d_%H%M%S).sql
tar -czf ~/backup_images_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/maji-kichi-backend/storage/app/public/images/
```
- [ ] SSHログイン成功
- [ ] データベースバックアップ作成
- [ ] 画像ファイルバックアップ作成
- [ ] バックアップファイル名記録: ________________

### Phase 2: 必須リネーム作業（超重要）
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/

# 現在の状態確認
ls -la shops/     # large, thumbnail が存在することを確認
ls -la reviews/   # large, thumbnail が存在することを確認

# リネーム実行
sudo mv shops/large shops/original
sudo mv reviews/large reviews/original

# 結果確認
ls -la shops/     # original, thumbnail になっていることを確認
ls -la reviews/   # original, thumbnail になっていることを確認

# 権限設定
sudo chown -R www-data:www-data shops/original reviews/original
```
- [ ] shops/large → shops/original リネーム完了
- [ ] reviews/large → reviews/original リネーム完了
- [ ] 権限設定完了

### Phase 3: コードデプロイ
```bash
# メンテナンスモード開始
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan down --render="errors::503" --retry=60

# デプロイディレクトリへ移動
cd ~/deployment/maji-kichi-meshi/

# 最新コード取得
git pull origin main

# デプロイスクリプト実行（マイグレーション自動実行）
./deploy.sh
```
- [ ] メンテナンスモード開始
- [ ] git pull成功
- [ ] deploy.sh実行成功（マイグレーション自動実行）

### Phase 4: データ移行
```bash
cd /var/www/maji-kichi-backend

# データ移行コマンド実行（本番環境で）
sudo -u www-data php artisan shop-images:migrate-data
sudo -u www-data php artisan images:fix-uuid

# キャッシュクリア
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache

# メンテナンスモード解除
sudo -u www-data php artisan up
```
- [ ] shop-images:migrate-data 成功（件数: _____）
- [ ] images:fix-uuid 成功（件数: _____）
- [ ] キャッシュクリア完了
- [ ] メンテナンスモード解除

### Phase 5: 動作確認

#### API動作確認
```bash
# レビューAPI（画像URLs確認）
curl https://maji-kichi-meshi.takemitsu.net/api/reviews/1 | jq '.data.images'

# 画像配信API（遅延生成）
curl -I https://maji-kichi-meshi.takemitsu.net/api/images/reviews/small/[filename]
```
- [ ] レビューAPIでurls配列が返る
- [ ] 画像配信APIで200 OKが返る

#### フロントエンド確認
- [ ] 店舗詳細ページで画像表示
- [ ] レビュー一覧で画像表示
- [ ] 新規レビュー投稿で画像アップロード

#### ファイル生成確認
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/

# 初回アクセス後、新しいサイズが生成されているか確認
ls -la reviews/small/    # 遅延生成されたファイル
ls -la reviews/medium/   # 遅延生成されたファイル
```
- [ ] small/mediumディレクトリにファイル生成確認

#### データ整合性チェック
```bash
cd /var/www/maji-kichi-backend
sudo -u www-data php artisan tinker --execute="
    \$incomplete = \App\Models\ShopImage::whereNull('thumbnail_path')->count();
    echo 'Incomplete migrations: ' . \$incomplete;
"
```
- [ ] 不完全な移行がないことを確認

#### エラーログ確認
```bash
tail -f /var/www/maji-kichi-backend/storage/logs/laravel-$(date +%Y-%m-%d).log
```
- [ ] エラーなし、または想定内のエラーのみ

---

## 🔄 ロールバック手順（必要な場合）

### 1. マイグレーションロールバック
```bash
cd /var/www/maji-kichi-backend

# 画像遅延生成関連の3つのマイグレーションをロールバック
sudo -u www-data php artisan migrate:rollback --step=3

# ロールバック確認
php artisan migrate:status | grep 2025_09
```

### 2. ディレクトリ名を元に戻す
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/original shops/large
sudo mv reviews/original reviews/large
```

### 3. データベースリストア（最終手段）
```bash
# MySQLの場合
mysql -u maji_kichi_user -p maji_kichi_meshi < ~/backup_db_[timestamp].sql

# SQLiteの場合（開発環境）
cp database/backup_*.sqlite database/database.sqlite
```

---

## 🛠️ トラブルシューティング

### よくある失敗と対処法

#### 1. 「original画像が見つかりません」エラー
**原因**: largeディレクトリのリネームを忘れた
**対処**:
```bash
cd /var/www/maji-kichi-backend/storage/app/public/images/
sudo mv shops/large shops/original
sudo mv reviews/large reviews/original
```

#### 2. データ移行コマンドが0件処理
**原因**: すでに移行済み（冪等性により正常）
**対処**: 問題なし、次に進む

#### 3. 権限エラー
**原因**: www-dataユーザーの権限不足
**対処**:
```bash
sudo chown -R www-data:www-data /var/www/maji-kichi-backend/storage/
```

### ロールバック失敗時の対処

#### ケース1: NOT NULL制約エラー
```sql
-- 手動でNULL値を許可
ALTER TABLE review_images MODIFY large_path VARCHAR(255) NULL;
```

#### ケース2: インデックス重複エラー
```sql
-- 既存インデックスを削除
DROP INDEX shop_images_shop_id_index ON shop_images;
```

#### ケース3: カラムが既に削除されている
```bash
# エラーを無視して次のステップへ進む
php artisan migrate:rollback --step=1 --force
```

### 発見された問題と対策済み事項

1. **NOT NULL制約によるロールバック失敗**
   - 対策: large_pathはnullableのまま保持

2. **インデックス重複エラー**
   - 対策: インデックス存在確認を追加

3. **データ移行コマンドの前提条件不足**
   - 対策: カラム存在確認を追加

---

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

---

## 🧪 テストツール

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

---

## 📝 デプロイ記録テンプレート

| 項目 | 内容 |
|------|------|
| デプロイ日時 | |
| 実行者 | |
| バックアップファイル | DB: ____________ <br> Images: ____________ |
| マイグレーション結果 | |
| データ移行件数 | ShopImage: _____ <br> ReviewImage: _____ |
| 問題発生 | なし / あり（詳細：___________） |
| ロールバック実施 | なし / あり（理由：___________） |

---

## 🔗 関連ドキュメント

- [リハーサル計画](./rehearsal-plan.md) - 開発環境での事前テスト
- [パターンAテスト結果](./pattern-a-test-result.md) - 実施済みテストの記録
- [実装計画](./01-implementation-plan.md) - 初期実装計画
- [進捗管理](./progress.md) - 開発進捗記録

---

**重要**: このドキュメントは画像遅延生成機能のマイグレーションに特化しています。
他のマイグレーションには適用しないでください。

特に「**largeディレクトリのリネーム**」は絶対に忘れないこと！