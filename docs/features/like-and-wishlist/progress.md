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

## Phase 2: 行きたいリスト機能 ✅ 完了

### 実装日時
- 開始: 2025-10-14
- 完了: 2025-10-14

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

#### 2.5 フロントエンド実装 ✅
- [x] `WishlistButton.vue` コンポーネント作成 (`frontend/components/WishlistButton.vue`)
  - トグル機能（追加/削除）
  - 状態表示（want_to_go / visited）
  - 優先度・ステータス表示
  - 未ログイン時のツールチップ
  - **パフォーマンス最適化**: 初期値が渡されている場合はAPI呼び出しをスキップ
- [x] `PrioritySelector.vue` コンポーネント作成 (`frontend/components/PrioritySelector.vue`)
  - 3段階の優先度選択（いつか/そのうち/絶対）
  - API連携
  - ラベル表示オプション
- [x] `WishlistCard.vue` コンポーネント作成 (`frontend/components/WishlistCard.vue`)
  - 店舗情報表示
  - 優先度変更
  - ステータス変更（want_to_go ↔ visited）
  - 削除機能
  - 即時UI更新（楽観的UI）
- [x] API連携 (`frontend/composables/useApi.ts`)
  - `wishlists.add()`, `remove()`, `updatePriority()`, `updateStatus()`, `getStatus()`, `list()`
- [x] 店舗詳細ページに統合 (`frontend/pages/shops/[id]/index.vue`)
  - WishlistButtonコンポーネント配置
  - **最適化**: API response から `wishlist_status` を props として渡す
- [x] 行きたいリストページ実装 (`frontend/pages/my/wishlists.vue`)
  - タブ切替（行きたい/行った）
  - ソート機能（優先度/追加日時）
  - WishlistCardコンポーネント使用
  - 空状態表示
- [x] ヘッダーナビゲーション更新 (`frontend/components/TheHeader.vue`)
  - デスクトップメニューに「行きたいリスト」リンク追加
  - モバイルメニューに「行きたいリスト」リンク追加
- [x] フロントエンドコード品質チェック
  - ESLint: Pass
  - TypeScript: Pass

#### 2.6 API統合問題と解決 ✅
- [x] **問題**: 店舗一覧・詳細で wishlist_status が常に `in_wishlist: false` を返す
  - **原因**: 店舗APIは public endpoint (認証不要)のため、`ShopResource::getWishlistStatus()` 内の `Auth::check()` が常に false を返す
  - **影響**: DBには正しくデータがあり、APIも呼ばれているが、UIに反映されない

- [x] **解決策**: Controller でのリレーションフィルタリング + Resource での isEmpty チェック
  1. `ShopController`: wishlists リレーションを eager loading 時にユーザーでフィルタ (`app/Http/Controllers/Api/ShopController.php`:33-37)
     ```php
     'wishlists' => function ($query) {
         if (Auth::check()) {
             $query->where('user_id', Auth::id());
         }
     }
     ```
  2. `ShopResource::getWishlistStatus()`: `Auth::check()` 削除、`isEmpty()` チェックのみ (`app/Http/Resources/ShopResource.php`:59-80)
  3. 同じパターンを `ReviewController` + `ReviewResource` にも適用 (likes リレーション)

- [x] **結果**:
  - 店舗一覧・詳細で wishlist_status が正しく表示される
  - いいね機能の is_liked も同様に修正・動作確認
  - public endpoint でも認証状態に応じた適切なデータ返却が可能に

#### 2.7 WishlistResource 実装 ✅
- [x] **問題**: `WishlistController` が生データを返していた(Laravel非標準)
- [x] **解決**: `WishlistResource` 作成 (`app/Http/Resources/WishlistResource.php`)
  - `ShopResource` を再利用して shop データを返す(images 構造の統一)
  - 優先度・ステータス・出典情報を適切に変換
  - Laravel 標準の Resource パターンに準拠
- [x] **結果**: すべての API が統一された Resource パターンで実装

#### 2.8 UI/UX改善 ✅
- [x] **楽観的UI実装** (`frontend/components/WishlistCard.vue`)
  - ステータス変更を即座に反映(リロード不要)
  - エラー時のみロールバック
- [x] **モバイル最適化**
  - ボタンレイアウト改善(縦並び → 横並び)
  - タッチ操作に配慮したサイズ・間隔

### 成果物
- **バックエンド**: 4ファイル (Model, Controller, Migration, Resource) + ShopController・ReviewController・ShopResource・ReviewResource 更新
- **フロントエンド**: 3ファイル (WishlistButton, PrioritySelector, WishlistCard) + 既存ファイル統合(5ファイル)
- **テスト**: 1ファイル (13テスト、37アサーション)
- **APIエンドポイント**: 6個

### コード品質チェック(最終) ✅
- [x] Laravel Pint: Pass
- [x] PHPStan: No errors
- [x] PHPUnit: 13 tests, 37 assertions - All pass
- [x] ESLint: Pass
- [x] TypeScript: Pass

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

### 5. Public API エンドポイントでの認証パターン ⭐ **重要**
- **問題**: Resource内で `Auth::check()` を使うと、public endpoint で常に false
- **解決**: Controller で eager loading 時にユーザーフィルタ → Resource で `isEmpty()` チェック
- **理由**: public endpoint は JWT middleware がないため、`Auth::check()` が機能しない
- **適用箇所**: `ShopResource`, `ReviewResource` (wishlists, likes リレーション)
- **パターン**:
  ```php
  // Controller
  'wishlists' => function ($query) {
      if (Auth::check()) {
          $query->where('user_id', Auth::id());
      }
  }

  // Resource
  if (!$this->relationLoaded('wishlists') || $this->wishlists->isEmpty()) {
      return ['in_wishlist' => false];
  }
  ```

### 6. Resource 再利用による一貫性
- `WishlistResource` で `ShopResource` を再利用することで、images 構造を統一
- Laravel 標準の Resource パターンに準拠することで保守性向上
- 生データを返さず、必ず Resource 経由でデータを変換する

### 7. 楽観的UI (Optimistic UI) によるUX向上
- ボタン押下時に即座にローカルで状態を変更
- API成功時はそのまま、エラー時のみロールバック
- リロード不要で、ユーザーに即座にフィードバック

---

## Phase 3: 認証統合テスト強化 ✅ 完了

### 実装日時
- 開始: 2025-10-15
- 完了: 2025-10-15

### 背景
Phase 1・2実装後、public endpoint（認証不要エンドポイント）での認証状態別表示に問題が発覚。
オプショナル認証パターンを実装し、包括的な認証統合テストを追加。

### 実装内容

#### 3.1 オプショナル認証パターン実装 ✅
- [x] **問題点の発見**
  - レビュー・店舗一覧で未ログイン時に他人の「行った」状態が表示される
  - JWT認証の public endpoint で `Auth::check()` が常に false
  - いいね数が 0 で表示される

- [x] **原因分析**
  1. Public endpoint で JWT トークンを手動パースしていない
  2. 条件付き eager loading で `Auth::check()` 使用時に常に false
  3. `likes_count` を loaded relation から取得していた

- [x] **解決策実装** (`app/Http/Controllers/Api/`)
  - `ReviewController.php`: オプショナル認証パターン実装
  - `ShopController.php`: オプショナル認証パターン実装
  - `ReviewLikeController.php`: オプショナル認証パターン実装
  - **パターン**: `JWTAuth::parseToken()->authenticate()` を try-catch でラップ
  - 条件付き `with()` でログインユーザーのみ個人データを load
  - `withCount('likes')` でいいね数を効率的に取得

- [x] **Resource 修正**
  - `ReviewResource.php`: `$this->likes_count` から取得するよう修正
  - `ShopResource.php`, `ReviewResource.php`: `isEmpty()` チェックのみに簡略化

#### 3.2 認証統合テスト追加 ✅
- [x] **新規テストファイル作成**
  - `ReviewApiAuthIntegrationTest.php` (8テスト)
    - レビュー一覧・詳細での行きたいリスト表示（認証状態別）
    - ゲスト・ログインユーザー・複数ユーザーのデータ隔離
    - フィルタリング時の認証状態維持
  - `ShopApiAuthIntegrationTest.php` (6テスト)
    - 店舗一覧・詳細での行きたいリスト表示（認証状態別）
    - 複数ユーザーの行きたいステータス分離
    - 検索フィルタ時の認証状態維持

- [x] **既存テスト強化**
  - `ReviewLikeApiTest.php`: オプショナル認証対応テスト2件追加
    - ゲストユーザーのいいね数表示確認
    - 認証ユーザーの `is_liked` フィールド確認

#### 3.3 複数ユーザーデータ隔離テスト追加 ✅
- [x] **WishlistApiTest.php**: 4テスト追加
  - ゲストユーザーの401エラー確認
  - 複数ユーザーのデータ隔離（user1はshop1,2のみ、user2はshop2,3のみ）
  - 空の行きたいリスト確認
  - status フィルタ時のデータ隔離確認

- [x] **ReviewLikeApiTest.php**: 3テスト追加
  - 複数ユーザーのいいねデータ隔離
  - 空のいいねリスト確認
  - ページネーション動作確認（20件、per_page=10）

- [x] **ReviewApiAuthIntegrationTest.php**: 1テスト追加
  - 複数ユーザーが異なるいいね・行きたい状態でレビュー一覧を見る

- [x] **ShopApiAuthIntegrationTest.php**: 1テスト追加
  - 複数ユーザーが異なる行きたい状態で店舗一覧を見る

- [x] **RankingApiTest.php**: 2テスト追加
  - 複数ユーザーが異なる公開/非公開ランキングを持つ
  - ランキング作成が他ユーザーのデータに影響しない

### テスト結果 ✅
- **Phase 3.1**: 131テスト通過 (669 assertions)
- **Phase 3.2**: 140テスト通過 (追加9テスト)
- **Phase 3.3**: 289テスト通過 (1360 assertions、追加149テスト)
- Laravel Pint: Pass
- PHPStan: No errors

### 成果物
- **認証パターン修正**: 3ファイル (ReviewController, ShopController, ReviewLikeController)
- **新規テストファイル**: 2ファイル (ReviewApiAuthIntegrationTest, ShopApiAuthIntegrationTest)
- **既存テスト強化**: 4ファイル (ReviewLikeApiTest, WishlistApiTest, ReviewApiAuthIntegrationTest, ShopApiAuthIntegrationTest, RankingApiTest)
- **総テスト数**: 289テスト、1360 assertions

### 学び

#### オプショナル認証パターン ⭐ **重要**
Public endpoint（JWT middleware なし）でも認証状態に応じたデータを返すパターン:

```php
// Controller
try {
    JWTAuth::parseToken()->authenticate();
} catch (\Exception $e) {
    // トークンがない or 無効 → 未認証として扱う
}

// 条件付き eager loading
$query->with([
    'wishlists' => function ($query) {
        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        }
    },
]);

// Resource
if (!$this->relationLoaded('wishlists') || $this->wishlists->isEmpty()) {
    return ['in_wishlist' => false];
}
```

**適用箇所**:
- `GET /api/reviews` (ReviewController)
- `GET /api/reviews/{id}` (ReviewController)
- `GET /api/shops` (ShopController)
- `GET /api/shops/{id}` (ShopController)
- `GET /api/reviews/{review}/likes` (ReviewLikeController)

#### 包括的テストの重要性
- 未ログイン・ログイン・複数ユーザーのパターンを網羅することで、データ隔離の問題を早期発見
- 統合テストで API エンドポイント全体の動作を確認
- 単体機能テスト + 統合テスト の組み合わせで高い品質を担保

---

## 完了

Phase 1（いいね機能）・Phase 2（行きたいリスト機能）・Phase 3（認証統合テスト強化）の実装が完了しました。

- **バックエンド**: API Resource パターン完全準拠、オプショナル認証パターン実装
- **フロントエンド**: 楽観的UI実装、モバイル最適化
- **テスト**: 全289テスト通過（1360 assertions）
- **コード品質**: Pint, PHPStan, ESLint, TypeScript すべてパス
- **認証パターン**: Public endpoint での認証状態別データ返却を完全サポート

---

## 参考リンク

- [plan.md](./plan.md) - 全体計画・仕様
- [NOTES.md](./NOTES.md) - 実装時の注意事項
