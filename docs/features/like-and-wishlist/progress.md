# いいね＆行きたいリスト機能 - 実装進捗

## Phase 1: いいね機能 ✅ 完了

### 実装日時
- 開始: 2025-10-14
- 完了: 2025-10-14

### 実装内容

#### 1.1 データベース準備 ✅
- [x] マイグレーション作成: `2025_01_14_000001_create_review_likes_table.php`
- [x] マイグレーション実行・確認
  - `review_likes` テーブル作成完了
  - UNIQUE制約: `unique_user_review (user_id, review_id)`
  - インデックス: `idx_review_id`, `idx_user_created`

#### 1.2 バックエンド実装 ✅
- [x] `ReviewLike` モデル作成 (`app/Models/ReviewLike.php`)
  - リレーション: `user()`, `review()`
- [x] `ReviewLikeRepository` 作成 (`app/Repositories/ReviewLikeRepository.php`)
  - `toggle()`: いいねトグル（追加/削除）
  - `getCount()`: いいね数取得
  - `isLiked()`: いいね済みか確認
  - `getUserLikedReviews()`: ユーザーのいいねリスト
- [x] `ReviewLikeController` 作成 (`app/Http/Controllers/Api/ReviewLikeController.php`)
  - `toggle()`: いいねトグル
  - `show()`: いいね数＆状態取得
  - `myLikes()`: 自分のいいねリスト
- [x] APIルート追加 (`routes/api.php`)
  - `POST /api/reviews/{review}/like` → トグル機能
  - `GET /api/reviews/{review}/likes` → いいね数＆状態取得
  - `GET /api/my-liked-reviews` → 自分のいいねリスト
- [x] `Review` モデルにリレーション追加
  - `likes()` リレーション
  - `likesCount` 属性（withCount用）

#### 1.3 フロントエンド実装 ✅
- [x] `LikeButton.vue` コンポーネント作成 (`frontend/components/LikeButton.vue`)
  - いいねトグル機能
  - カウント表示
  - ローディング状態
  - 未ログイン時のツールチップ表示
  - **パフォーマンス最適化**: 初期値が渡されている場合はAPI呼び出しをスキップ
- [x] レビュー一覧に統合 (`frontend/pages/reviews/index.vue`)
  - LikeButtonコンポーネント配置
  - **最適化**: API response から `likes_count` と `is_liked` を props として渡す
- [x] レビュー詳細に統合 (`frontend/pages/reviews/[id]/index.vue`)
  - LikeButtonコンポーネント配置
  - **最適化**: API response から `likes_count` と `is_liked` を props として渡す
- [x] マイページいいねリスト実装 (`frontend/pages/my/liked-reviews.vue`)
  - ページネーション対応
  - レビューカード表示
  - 空状態表示
- [x] ヘッダーナビゲーション更新 (`frontend/components/TheHeader.vue`)
  - デスクトップメニューに「いいねしたレビュー」リンク追加
  - モバイルメニューに「いいねしたレビュー」リンク追加

#### 1.4 テスト実装 ✅
- [x] Feature Test: `ReviewLikeTest.php` (11テスト、30アサーション)
  - いいね追加/削除のトグル動作
  - いいね数カウント
  - 重複いいね防止
  - 未認証ユーザーエラー
  - いいねリスト取得
  - 存在しないレビューへのいいねエラー
  - **全テストパス** ✅

#### 1.5 パフォーマンス最適化 ✅
- [x] **N+1クエリ問題の解決**
  - **問題**: レビュー一覧でLikeButtonごとに個別API呼び出し（10〜20回）
  - **解決策**:
    1. `ReviewResource.php`: `likes_count` と `is_liked` を API response に追加
    2. `ReviewController.php`: `likes` リレーションを eager loading (`with(['likes'])`)
    3. `LikeButton.vue`: 初期値が渡されている場合は API 呼び出しをスキップ
    4. 各ページ: `initial-likes-count` と `initial-is-liked` props を渡す
  - **結果**: API呼び出し数が **20回 → 0回** に削減 🎉

- [x] **ゲストユーザーのいいね数表示**
  - **問題**: 未ログイン時にいいね数が表示されない
  - **解決策**: `ReviewResource.php` で常に `likes_count` を返す（`is_liked` は認証時のみ true/false、未認証時は false）
  - **結果**: ゲストユーザーもいいね数を確認可能 ✅

#### 1.6 UI/UX改善 ✅
- [x] **いいねしたレビュー一覧のUI最適化**
  - コンパクト表示（コメント2行省略、画像3枚表示）
  - ユーザー情報の表示順変更（訪問日→ユーザー名）
  - モバイルパディング最適化（p-3）
  - 画像サイズ向上（thumbnail→small、w-16 h-16）
  - いいね数の正しい表示（review.likes_count使用）
- [x] **並び順の修正**
  - **問題**: `pluck('review')` で IN句使用時に順序が保証されない
  - **解決**: `getCollection()->map()` で順序を保持
  - **結果**: いいね解除→再いいね で正しく一番上に表示される

### コード品質チェック ✅
- [x] Laravel Pint: Pass
- [x] PHPStan: No errors
- [x] PHPUnit: 11 tests, 30 assertions - All pass
- [x] ESLint: Pass
- [x] TypeScript: Pass

### ブラウザテスト ✅
- [x] Chrome DevTools MCP Server でのテスト
  - レビュー一覧ページ: いいねボタン表示・カウント表示 ✅
  - レビュー詳細ページ: いいねボタン表示・カウント表示 ✅
  - 未ログイン時: ボタン無効化・ツールチップ表示 ✅
  - ログイン時: トグル動作確認 ✅
  - いいねリストページ: 一覧表示・ページネーション ✅

### 成果物
- バックエンド: 3ファイル (Model, Repository, Controller) + Migration
- フロントエンド: 2ファイル (Component, Page) + 既存ファイル統合
- テスト: 1ファイル (11テスト、30アサーション)
- API呼び出し削減: 20回 → 0回（N+1問題解決）

---

## Phase 2: 行きたいリスト機能 🚧 バックエンド完了

### 実装日時
- 開始: 2025-10-14
- バックエンド完了: 2025-10-14

### 実装内容

#### 2.1 データベース準備 ✅
- [x] マイグレーション作成: `2025_10_14_095033_create_wishlists_table.php`
- [x] マイグレーション実行・確認
  - `wishlists` テーブル作成完了
  - UNIQUE制約: `unique_user_shop (user_id, shop_id)`
  - インデックス: `idx_wishlist_user_priority`, `idx_wishlist_user_created`
  - 外部キー: CASCADE (user, shop), SET NULL (source_user, source_review)

#### 2.2 バックエンド実装 ✅
- [x] `Wishlist` モデル作成 (`app/Models/Wishlist.php`)
  - リレーション: `user()`, `shop()`, `sourceUser()`, `sourceReview()`
  - アクセサ: `priorityLabel` (いつか/そのうち/絶対)
  - **Laravel 11/12 Attribute::make() パターン使用**
- [x] `WishlistController` 作成 (`app/Http/Controllers/Api/WishlistController.php`)
  - **Repository パターン不使用** (Laravel標準、既存コード ReviewLikeController と統一)
  - `store()`: 追加
  - `destroy()`: 削除
  - `updatePriority()`: 優先度変更
  - `updateStatus()`: 状態変更（want_to_go → visited）
  - `index()`: リスト取得
- [x] `ShopController` に追加 (`app/Http/Controllers/Api/ShopController.php`)
  - `wishlistStatus()`: 特定店舗の行きたい状態確認
- [x] APIルート追加 (`routes/api.php`)
  - `POST /api/my-wishlist` → 追加
  - `DELETE /api/my-wishlist/{shop}` → 削除
  - `PATCH /api/my-wishlist/{shop}/priority` → 優先度変更
  - `PATCH /api/my-wishlist/{shop}/status` → 状態変更
  - `GET /api/my-wishlist` → リスト取得
  - `GET /api/shops/{shop}/wishlist-status` → 店舗の行きたい状態確認
- [x] `Shop` モデルにリレーション追加
  - `wishlists()` リレーション

#### 2.3 テスト実装 ✅
- [x] Feature Test: `WishlistApiTest.php` (13テスト、全パス)
  - いいね追加/削除
  - 重複追加防止 (409 Conflict)
  - 未認証エラー (401 Unauthorized)
  - 優先度変更
  - 状態変更（visited_at 自動設定）
  - リスト取得（status フィルタ、priority ソート）
  - public endpoint の動作確認
  - 出典情報記録 (source_type, source_user_id, source_review_id)
  - バリデーション (priority 範囲チェック)
  - CASCADE削除 (shop削除時にwishlist削除)
  - **全テストパス** ✅

#### 2.4 コード品質チェック ✅
- [x] Laravel Pint: Pass
- [x] PHPStan: No errors (PHPDoc `@property-read` で対応)
- [x] PHPUnit: 13 tests, 37 assertions - All pass
- [x] **Laravel標準・ベストプラクティス準拠確認**:
  - Eloquent リレーション、Attribute Accessor、バリデーション、エラーハンドリングすべて適切
  - Repository パターン不使用は既存コードとの一貫性を優先（問題なし）

#### 2.5 フロントエンド実装 ⏸️ 未実装
- [ ] `WishlistButton.vue` コンポーネント作成
- [ ] `PrioritySelector.vue` コンポーネント作成
- [ ] API連携 (`composables/useWishlists.ts`)
- [ ] 店舗詳細ページに統合
- [ ] レビューカードに統合
- [ ] 行きたいリストページ実装 (`frontend/pages/my/wishlists.vue`)
- [ ] フロントエンドコード品質チェック
- [ ] ブラウザテスト

### 成果物（バックエンドのみ）
- バックエンド: 3ファイル (Model, Controller, Migration) + ShopController 更新
- テスト: 1ファイル (13テスト、37アサーション)
- APIエンドポイント: 6個

---

## 実装時の学び

### 1. パフォーマンス最適化の重要性
- コンポーネント単位で個別にAPIを呼ぶと N+1 問題が発生
- 親コンポーネントから初期データを props で渡すことで解決
- Laravel の eager loading (`with()`) + API Resource の `whenLoaded()` が効果的

### 2. ゲストユーザー体験の考慮
- いいね数は認証不要で表示することでエンゲージメント向上
- 未ログイン時は「ログインが必要です」のツールチップでUX改善

### 3. TypeScript の型安全性
- `types/api.ts` に `likes_count` と `is_liked` を追加することで型エラーを防止
- コンポーネント間のデータ受け渡しを型安全に

### 4. テストの重要性
- 11テスト・30アサーションで主要な動作をカバー
- トグル動作、重複防止、未認証エラーなど実装前に仕様を明確化

---

## 次のステップ

Phase 2（行きたいリスト機能）の実装開始時には以下を実施:

1. `plan.md` の Phase 2 セクションを再確認
2. `NOTES.md` の重要な仕様（削除動作、優先度UI）を再確認
3. Phase 1 の実装パターンを参考に、同様の構造で実装
4. パフォーマンスを考慮した設計（eager loading、初期値 props 渡し）

---

## 参考リンク

- [plan.md](./plan.md) - 全体計画・仕様
- [NOTES.md](./NOTES.md) - 実装時の注意事項
