# 開発環境リハーサル手順

**作成日**: 2025-09-21
**更新日**: 2025-09-21
**目的**: 本番デプロイ前の完全リハーサル

## 🎯 リハーサルパターン選択

### パターンA: 本番環境完全シミュレーション（推奨）
本番環境と同じ状況（旧構造→新構造への移行）を再現する

### パターンB: 現在状態での動作確認
すでに新構造になっている状態で、移行コマンドの冪等性と機能を確認

---

## 📋 パターンA: 本番環境完全シミュレーション

### 1. 事前準備

```bash
# プロジェクトルートへ
cd /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/backend

# 現在の状態を記録
php artisan migrate:status | grep 2025_09
```

### 2. 旧構造への巻き戻し

```bash
# 遅延生成関連のマイグレーション3つをロールバック
php artisan migrate:rollback --step=3

# ロールバック確認
php artisan migrate:status | grep 2025_09
# → 3つのマイグレーションが "Pending" になっていることを確認
```

### 3. 旧構造でのデータ準備

```bash
# フロントエンドから画像付きレビューを投稿
# http://localhost:3002 でログイン→レビュー投稿（画像添付）

# または、シーダーでデータ作成
php artisan db:seed --class=ImageSeeder

# 旧構造でのデータ確認
php artisan tinker
>>> \App\Models\ReviewImage::count()
>>> \App\Models\ShopImage::count()
>>> \App\Models\ReviewImage::first()->toArray()
```

### 4. 本番デプロイシミュレーション

```bash
# 新しいマイグレーションを適用
php artisan migrate

# マイグレーション確認
php artisan migrate:status | grep 2025_09
# → すべて "Ran" になっていることを確認

# データ移行コマンド実行
php artisan shop-images:migrate-data
php artisan images:fix-uuid

# 結果確認
php artisan tinker
>>> $img = \App\Models\ReviewImage::first();
>>> $img->uuid;  // ファイル名と一致することを確認
>>> $img->sizes_generated;  // 遅延生成状態を確認
```

---

## 📋 パターンB: 現在状態での動作確認

### 1. 現在の状態確認

```bash
# プロジェクトルートへ
cd /Users/takemitsusuzuki/work/personal/maji-kichi-meshi/backend

# マイグレーション状態確認（すべて適用済み）
php artisan migrate:status | grep 2025_09

# 既存データ確認
php artisan tinker
>>> \App\Models\ReviewImage::count()
>>> \App\Models\ShopImage::count()
```

### 2. データ移行コマンドの冪等性確認

```bash
# 1回目の実行
php artisan shop-images:migrate-data
php artisan images:fix-uuid

# 2回目の実行（エラーが出ないこと、データが壊れないことを確認）
php artisan shop-images:migrate-data
php artisan images:fix-uuid

# データの整合性確認
php artisan tinker
>>> \App\Models\ReviewImage::whereNull('uuid')->count();  // 0であること
>>> \App\Models\ShopImage::whereNull('uuid')->count();   // 0であること
```

### 3. 新規アップロード確認

```bash
# フロントエンドから画像アップロード
# http://localhost:3002 でレビュー投稿

# アップロード後の確認
ls -la storage/app/public/images/reviews/thumbnail/
ls -la storage/app/public/images/reviews/original/
# → thumbnailとoriginalのみ生成されていることを確認
```

### 4. 遅延生成動作確認

```bash
# 画像配信エンドポイントにアクセス（初回は生成のため遅い）
curl -I http://localhost:8000/api/images/reviews/small/[filename].jpg
# → 200 OKが返ることを確認

# storage確認（smallが生成されたことを確認）
ls -la storage/app/public/images/reviews/small/

# 2回目のアクセス（キャッシュされているため高速）
time curl -I http://localhost:8000/api/images/reviews/small/[filename].jpg
```

---

## ✅ 確認項目チェックリスト

### パターンA実施時
- [ ] ロールバック成功（3つのマイグレーションがPending）
- [ ] 旧構造でのデータ作成成功
- [ ] マイグレーション適用成功
- [ ] shop-images:migrate-data コマンド成功
- [ ] images:fix-uuid コマンド成功
- [ ] UUIDとファイル名の一致確認
- [ ] 遅延生成動作確認

### パターンB実施時
- [ ] データ移行コマンドの冪等性確認
- [ ] 新規アップロード（thumbnailのみ生成）
- [ ] 遅延生成動作（small/medium）
- [ ] APIレスポンスのurls配列確認

### 共通確認項目
- [ ] エラーログなし（storage/logs/）
- [ ] 画像表示正常（フロントエンド）
- [ ] 管理画面での画像表示正常

## 🚨 問題発生時の対処

### マイグレーションエラー
```bash
# エラー詳細確認
php artisan migrate:status
# 手動ロールバック
php artisan migrate:rollback --step=1
```

### データ移行エラー
```bash
# ドライラン実行
php artisan images:fix-uuid --dry-run
# 詳細ログ確認
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

### ファイル権限エラー
```bash
# storage権限確認
ls -la storage/app/public/images/
# 権限修正
chmod -R 775 storage/app/public/images/
```

## 📝 リハーサル実施記録

| 日時 | パターン | 実行者 | 結果 | 備考 |
|------|----------|--------|------|------|
| - | - | - | - | 未実施 |

---

**重要**:
- パターンAは本番環境の完全シミュレーション（推奨）
- パターンBは現在の状態での簡易確認
- 本番デプロイ前には必ずパターンAを実施すること