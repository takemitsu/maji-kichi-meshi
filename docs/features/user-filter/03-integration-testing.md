# 統合テスト・動作確認

ユーザーフィルタ機能の統合テスト・動作確認タスクです。

## テスト環境準備

### テストデータ作成
```bash
# Laravel シーダー実行でテストデータ準備
cd backend
php artisan migrate:fresh --seed

# または個別シーダー実行
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=ShopSeeder  
php artisan db:seed --class=ReviewSeeder
php artisan db:seed --class=RankingSeeder
```

### フロントエンド開発サーバー起動
```bash
cd frontend
npm run dev
```

## API動作確認

### 1. ユーザー情報API
```bash
# 正常系
curl -X GET "http://localhost:8000/api/users/1/info" | jq

# 期待するレスポンス
{
  "id": 1,
  "name": "Test User",
  "created_at": "2025-07-25T12:00:00.000000Z"
}

# 異常系 - 存在しないユーザー
curl -X GET "http://localhost:8000/api/users/99999/info" | jq
# 期待: 404 Not Found
```

### 2. レビューAPI（ユーザーフィルタ）
```bash
# ユーザーフィルタあり
curl -X GET "http://localhost:8000/api/reviews?user_id=1" | jq

# フィルタなし（既存動作確認）
curl -X GET "http://localhost:8000/api/reviews" | jq

# 複合フィルタ（shop_id + user_id）
curl -X GET "http://localhost:8000/api/reviews?shop_id=1&user_id=1" | jq

# 異常系 - 存在しないユーザー
curl -X GET "http://localhost:8000/api/reviews?user_id=99999" | jq
# 期待: 422 Validation Error
```

### 3. ランキングAPI（ユーザーフィルタ）
```bash
# ユーザーフィルタあり
curl -X GET "http://localhost:8000/api/rankings?user_id=1" | jq

# 複合フィルタ（category_id + user_id）
curl -X GET "http://localhost:8000/api/rankings?category_id=1&user_id=1" | jq
```

## 自動テスト実行

### バックエンドテスト
```bash
cd backend

# 新規追加テストの実行
php artisan test --filter UserApiTest
php artisan test --filter "can_filter_reviews_by_user"
php artisan test --filter "can_filter_rankings_by_user"

# 全体テスト（既存機能への影響確認）
php artisan test
```

### フロントエンドテスト
```bash
cd frontend

# 型チェック
npm run type-check

# Lint チェック
npm run lint

# ビルドテスト
npm run build
```

## 手動動作確認

### シナリオ1: レビュー詳細からユーザーフィルタ
1. **開始**: `http://localhost:3000/reviews/1` にアクセス
2. **操作**: レビュー投稿者名をクリック
3. **期待**: `/reviews?user_id=X` に遷移
4. **確認**: 
   - ページタイトルが「○○さんのレビュー」に変更
   - 該当ユーザーのレビューのみ表示
   - 「全レビューを見る」リンク動作

### シナリオ2: 店舗詳細からユーザーフィルタ
1. **開始**: `http://localhost:3000/shops/1` にアクセス
2. **操作**: レビュー一覧でユーザー名をクリック
3. **期待**: `/reviews?user_id=X` に遷移
4. **確認**: 同上

### シナリオ3: ランキング詳細からユーザーフィルタ
1. **開始**: `http://localhost:3000/rankings/1` にアクセス
2. **操作**: ランキング作成者名をクリック
3. **期待**: `/rankings?user_id=X` に遷移
4. **確認**:
   - ページタイトルが「○○さんのランキング」に変更
   - 該当ユーザーのランキングのみ表示

### シナリオ4: 複合フィルタ動作
1. **操作**: `/reviews?shop_id=1&user_id=1` に直接アクセス
2. **確認**: 特定店舗×特定ユーザーの絞り込み動作

### シナリオ5: エラーハンドリング
1. **操作**: `/reviews?user_id=99999` に直接アクセス
2. **期待**: 適切なエラーページ表示

## モバイル動作確認

### デバイス・ブラウザ
- **iOS Safari** (iPhone 12/13/14)
- **Android Chrome** (Pixel/Galaxy)
- **レスポンシブモード** (Chrome DevTools)

### 確認項目
- [ ] ユーザー情報表示エリアの表示崩れなし
- [ ] ユーザー名リンクのタップ領域適切
- [ ] ページタイトルの文字切れなし
- [ ] 「全レビュー/全ランキングを見る」リンクの表示

## パフォーマンステスト

### レスポンス時間測定
```bash
# ユーザーフィルタありのレスポンス時間
curl -o /dev/null -s -w "%{time_total}\n" "http://localhost:8000/api/reviews?user_id=1"

# フィルタなしと比較
curl -o /dev/null -s -w "%{time_total}\n" "http://localhost:8000/api/reviews"
```

### データベースクエリ確認  
```php
// backend で DB::enableQueryLog() を使用してクエリログ確認
DB::enableQueryLog();
// API呼び出し実行
dd(DB::getQueryLog());
```

### N+1クエリチェック
- 複数ユーザーのレビュー/ランキング表示時のクエリ数確認
- `with(['user', 'shop'])` の効果確認

## SEO・メタタグ確認

### 検索エンジン最適化
```bash
# ページのメタタグ確認
curl -s "http://localhost:3000/reviews?user_id=1" | grep -E "<title>|<meta.*description"

# 期待するタイトル例
<title>山田太郎さんのレビュー | マジキチメシ</title>
<meta name="description" content="山田太郎さんが投稿したレビューの一覧です。">
```

### Open Graph対応確認
- Facebook/Twitter シェア時のプレビュー
- 適切なOGタグ設定

## アクセシビリティ確認

### WAVE・axe-core ツール
- 自動アクセシビリティチェック実行
- 色コントラスト比確認
- フォーカス順序確認

### キーボードナビゲーション
- [ ] Tabキーでユーザーリンクにフォーカス移動
- [ ] Enterキーでリンクアクティベート
- [ ] フォーカス表示の視認性

## 完了チェックリスト

### API動作確認
- [ ] ユーザー情報API（正常系・異常系）
- [ ] レビューAPIユーザーフィルタ（単体・複合）
- [ ] ランキングAPIユーザーフィルタ（単体・複合）
- [ ] バリデーションエラー処理

### 自動テスト
- [ ] バックエンド新規テスト全通過
- [ ] 既存テスト全通過（回帰テスト）
- [ ] フロントエンド型チェック・Lint通過
- [ ] ビルド成功

### 手動テスト（主要シナリオ）
- [ ] レビュー詳細→ユーザーフィルタ遷移
- [ ] 店舗詳細→ユーザーフィルタ遷移  
- [ ] ランキング詳細→ユーザーフィルタ遷移
- [ ] 複合フィルタ動作
- [ ] エラーハンドリング

### 品質確認
- [ ] モバイル表示・操作
- [ ] パフォーマンス（レスポンス時間）
- [ ] SEOメタタグ
- [ ] アクセシビリティ
- [ ] 既存機能への影響なし

## 不具合時の対応

### 一般的な問題
1. **API 404エラー**: ルート定義確認
2. **CORS エラー**: Laravel CORS設定確認  
3. **TypeScript エラー**: 型定義確認
4. **バリデーションエラー**: リクエストパラメータ確認

### デバッグ方法
```bash
# Laravel ログ確認
tail -f backend/storage/logs/laravel.log

# Nuxt.js エラー確認
# ブラウザコンソール・Network タブ
```

### 緊急時ロールバック
- 既存機能に影響する場合は該当コードをコメントアウト
- 新規ファイルは削除
- マイグレーション ロールバック（必要に応じて）

## 完了後の確認事項

### ドキュメント更新
- [ ] `technical-specs.md` に機能追加記録
- [ ] API仕様書更新（必要に応じて）

### デプロイ準備
- [ ] プロダクション環境での動作確認計画
- [ ] データベースマイグレーション確認
- [ ] 環境変数・設定確認