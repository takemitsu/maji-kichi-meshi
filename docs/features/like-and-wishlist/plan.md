# いいね＆行きたいリスト機能 - 実装計画

## 機能概要

### 1. いいね機能
- **対象**: レビュー投稿そのもの
- **目的**:
  - 「このレビュー参考になった」「面白い投稿」「同感」という評価
  - ユーザーエンゲージメント向上
  - 後から見返せるブックマーク的な使い方
- **表示**: レビューカードに「👍 いいね 23」カウント表示
- **マイページ**: いいねしたレビューのリスト表示

### 2. 行きたいリスト機能
- **対象**: 店舗そのもの
- **目的**:
  - 「この店に行ってみたい」候補リスト管理
  - 優先度管理（★3段階）
  - 「行った」状態への遷移 → レビュー執筆促進
  - データ蓄積促進
- **コアバリュー**: 他人のレビュー/ランキングを見て「行きたい」と思う導線
- **出典記録**: 誰のレビューを見て追加したか記録（将来の通知機能用）

---

## DB設計

### テーブル1: `review_likes`（レビューへのいいね）

```sql
CREATE TABLE review_likes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT 'いいねしたユーザー',
    review_id BIGINT UNSIGNED NOT NULL COMMENT 'いいね対象のレビュー',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_review (user_id, review_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    INDEX idx_review_id (review_id),  -- いいね数集計用
    INDEX idx_user_created (user_id, created_at DESC)  -- ユーザーのいいねリスト表示用
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### テーブル2: `wishlists`（店舗への行きたい）

```sql
CREATE TABLE wishlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '行きたいリストの所有者',
    shop_id BIGINT UNSIGNED NOT NULL COMMENT '行きたい店舗',
    status ENUM('want_to_go', 'visited') DEFAULT 'want_to_go' COMMENT '状態: 行きたい/行った',

    -- 優先度（★3段階: 1=低, 2=中, 3=高）
    priority TINYINT UNSIGNED DEFAULT 2 COMMENT '優先度: 1=いつか, 2=そのうち, 3=絶対',

    -- 出典情報（重要: 将来の通知機能用）
    source_type ENUM('review', 'shop_detail') NOT NULL COMMENT '追加経路',
    source_user_id BIGINT UNSIGNED NULL COMMENT '誰のレビューを見て追加したか',
    source_review_id BIGINT UNSIGNED NULL COMMENT 'どのレビューを見て追加したか',

    visited_at TIMESTAMP NULL COMMENT '訪問日時（行ったに変更した日時）',
    memo TEXT NULL COMMENT 'メモ（将来機能）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_shop (user_id, shop_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (source_review_id) REFERENCES reviews(id) ON DELETE SET NULL,
    INDEX idx_user_priority (user_id, status, priority DESC, created_at DESC),  -- 優先度順表示用
    INDEX idx_user_created (user_id, status, created_at DESC)  -- 追加日順表示用
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## API設計

### いいね機能API

#### 1. いいねをトグル（追加/削除）
```
POST /api/reviews/{review}/like
Authorization: Bearer {token}

Response 200:
{
  "message": "いいねしました",  // または "いいねを取り消しました"
  "is_liked": true,  // または false
  "likes_count": 24
}
```

#### 2. レビューのいいね数＆自分の状態取得
```
GET /api/reviews/{review}/likes
Authorization: Bearer {token} (optional)

Response 200:
{
  "likes_count": 23,
  "is_liked": true  // ログイン時のみ
}
```

#### 3. 自分がいいねしたレビュー一覧
```
GET /api/my-liked-reviews
Authorization: Bearer {token}

Response 200:
{
  "data": [
    {
      "id": 123,
      "shop": { "id": 45, "name": "うしごろバンビーナ" },
      "user": { "id": 1, "name": "鈴木" },
      "rating": 5,
      "comment": "最高の肉質！",
      "liked_at": "2025-01-15T10:30:00Z"
    }
  ],
  "meta": { ... }
}
```

---

### 行きたいリスト機能API

#### 1. 行きたいリストに追加
```
POST /api/my-wishlist
Authorization: Bearer {token}

Request:
{
  "shop_id": 45,
  "priority": 2,  // optional, default=2 (1=いつか, 2=そのうち, 3=絶対)
  "source_type": "review",  // or "shop_detail"
  "source_user_id": 1,  // optional
  "source_review_id": 123  // optional
}

Response 201:
{
  "message": "行きたいリストに追加しました",
  "data": { ... }
}
```

#### 2. 行きたいリストから削除
```
DELETE /api/my-wishlist/{shop}
Authorization: Bearer {token}

Response 200:
{
  "message": "行きたいリストから削除しました"
}
```

#### 3. 優先度変更
```
PATCH /api/my-wishlist/{shop}/priority
Authorization: Bearer {token}

Request:
{
  "priority": 3  // 1=いつか, 2=そのうち, 3=絶対
}

Response 200:
{
  "message": "優先度を変更しました",
  "data": { ... }
}
```

#### 4. 状態変更（「行った」に変更）
```
PATCH /api/my-wishlist/{shop}/status
Authorization: Bearer {token}

Request:
{
  "status": "visited"  // want_to_go → visited
}

Response 200:
{
  "message": "「行った」に変更しました。レビューを書きませんか？",
  "data": { ... }
}
```

#### 5. 行きたいリスト取得
```
GET /api/my-wishlist
Authorization: Bearer {token}

Query params:
- status=want_to_go (default) | visited
- sort=priority (default) | created_at

Response 200:
{
  "data": [
    {
      "shop": {
        "id": 45,
        "name": "うしごろバンビーナ",
        "avg_rating": 4.5,
        "category": { "name": "焼肉" }
      },
      "priority": 3,
      "priority_label": "絶対",
      "status": "want_to_go",
      "source": {
        "type": "review",
        "user": { "id": 1, "name": "鈴木" },
        "review_id": 123
      },
      "created_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

#### 6. 特定店舗の行きたい状態取得
```
GET /api/shops/{shop}/wishlist-status
Authorization: Bearer {token}

Response 200:
{
  "in_wishlist": true,
  "priority": 3,
  "priority_label": "絶対",
  "status": "want_to_go"
}

Response 404 (リストにない場合):
{
  "in_wishlist": false
}
```

---

## UI設計

### レビューカード

```
┌─────────────────────────────────┐
│ 【レビュー投稿】                │
│ 鈴木さん → うしごろバンビーナ   │
│ ★★★★★ 「最高の肉質！」       │
│ [写真]                          │
│                                 │
│ 👍 23  🔖 行きたい              │
│  ↑        ↑                     │
│ トグル   トグル（この店舗を追加）│
│                                 │
│ (追加済みの場合)                │
│ 👍 23  🔖 行きたい              │
│         ↑ 塗りつぶし+色変化     │
└─────────────────────────────────┘
```

**いいねボタン:**
- 未いいね: 👍 23 (グレー)
- いいね済: 👍 23 (青色)
- タップでトグル

**行きたいボタン:**
- 未追加: 🔖 行きたい (グレー、アウトライン)
- 追加済: 🔖 行きたい (青色、塗りつぶし)
- タップでトグル

---

### 店舗詳細ページ

```
┌─────────────────────────────────┐
│ うしごろバンビーナ              │
│ ★★★★☆ (23件のレビュー)       │
│                                 │
│ [🔖 行きたい]  [共有]          │ ← メインCTA
│  ↑ 状態により表示が変わる      │
│                                 │
│ ── レビュー一覧 ──              │
│                                 │
│ 鈴木さん ★★★★★               │
│ 「最高！」                      │
│ 👍 12                           │
└─────────────────────────────────┘
```

**行きたいボタンの状態表示（視覚的）:**
- **未登録**: `[🔖 行きたい]` (グレー背景、白文字)
  - タップ → リストに追加
- **status=want_to_go**: `[🔖 行きたい]` (青色背景、白文字)
  - タップ → リストから削除
- **status=visited**: `[✓ 行った]` (バッジ表示、ボタン無効)
  - **削除不可**（誤操作防止）
  - リストページからのみ削除可能

---

### マイページ - 行きたいリストタブ

```
/my/wishlists

┌──────────────────────────────────┐
│ [行きたい] [行った]              │
└──────────────────────────────────┘

【行きたいタブ】

並び替え: [優先度順▼] [追加日順]

── 絶対行きたい ──

┌─────────────────────────────────┐
│ うしごろバンビーナ              │
│ ★★★★☆ (焼肉)                 │
│                                 │
│ 行きたい度:                     │
│ [いつか] [そのうち] [絶対]      │
│  ↑灰色   ↑灰色    ↑赤色・選択中│
│                                 │
│ 鈴木さんのレビューから          │
│ [✓ 行った] [削除]               │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ 麺屋 一燈                       │
│ ★★★★★ (ラーメン)             │
│                                 │
│ 行きたい度:                     │
│ [いつか] [そのうち] [絶対]      │
│  ↑灰色   ↑黄色・選択中 ↑灰色   │
│                                 │
│ 店舗詳細から                    │
│ [✓ 行った] [削除]               │
└─────────────────────────────────┘

── そのうち行きたい ──

...

── いつか行きたい ──

...

【行ったタブ】

┌─────────────────────────────────┐
│ 大統領                          │
│ ★★★★☆ (焼肉)                 │
│ 訪問日: 2025-01-20              │
│ 山田さんのレビューから          │
│ [📝 レビューを書く] [削除]      │
└─────────────────────────────────┘
```

**削除動作の違い:**
- **店舗詳細ページ**: `status=visited` の場合、削除ボタン非表示（誤操作防止）
- **行きたいリストページ**: 「行きたい」「行った」両タブで [削除] ボタン表示

**優先度ボタンの視覚効果:**
- 未選択: `[いつか]` / `[そのうち]` / `[絶対]` (グレー背景、白文字)
- 選択中のみ: 色付き背景
  - `[いつか]` (灰色背景、白文字) ← priority: 1
  - `[そのうち]` (黄色背景、白文字) ← priority: 2
  - `[絶対]` (赤色背景、白文字) ← priority: 3
- タップで切り替え（選択式）
- **星は一切表示しない（データは priority: 1/2/3、UI表示は色のみ）**

### マイページ - いいねリストタブ

```
/my/liked-reviews

┌─────────────────────────────────┐
│ 👍 あなたがいいねしたレビュー   │
│                                 │
│ 鈴木さん → うしごろバンビーナ   │
│ ★★★★★ 「最高の肉質！」       │
│ いいねした日: 2025/01/15        │
│                                 │
│ [写真]                          │
│                                 │
│ ※ タップで元のレビューへ遷移   │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│ 山田さん → 一燈                 │
│ ★★★☆☆ 「普通かな」           │
│ いいねした日: 2025/01/10        │
└─────────────────────────────────┘
```

---

## 実装タスク一覧

### Phase 1: いいね機能（4〜5時間） ✅ 完了

#### 1.1 データベース準備（30分） ✅
- [x] マイグレーション作成: `create_review_likes_table`
- [x] マイグレーション実行・確認

#### 1.2 バックエンド実装（2時間） ✅
- [x] `ReviewLike` モデル作成
  - リレーション: `user()`, `review()`
- [x] `ReviewLikeRepository` 作成
  - `toggle()`: いいねトグル
  - `getCount()`: いいね数取得
  - `isLiked()`: いいね済みか確認
  - `getUserLikedReviews()`: ユーザーのいいねリスト
- [x] `ReviewLikeController` 作成
  - `toggle()`: いいねトグル（追加/削除を1つのメソッドで）
  - `show()`: いいね数＆状態取得
  - `myLikes()`: 自分のいいねリスト
- [x] APIルート追加 (`routes/api.php`)
  - `POST /api/reviews/{review}/like` → `ReviewLikeController@toggle`
  - `GET /api/reviews/{review}/likes` → `ReviewLikeController@show`
  - `GET /api/my-liked-reviews` → `ReviewLikeController@myLikes`
- [x] `Review` モデルにリレーション追加: `likes()`, `likesCount` 属性

#### 1.3 フロントエンド実装（1.5時間） ✅
- [x] `LikeButton.vue` コンポーネント作成
  - いいねトグル機能
  - カウント表示
  - ローディング状態
- [x] レビューカードに統合
  - `pages/reviews/index.vue`
  - `pages/reviews/[id]/index.vue`
- [x] マイページいいねリスト実装 (`frontend/pages/my/liked-reviews.vue`)
- [x] ヘッダーナビゲーション更新 (`frontend/components/TheHeader.vue`)

#### 1.4 テスト実装（1時間） ✅
- [x] Feature Test: `ReviewLikeTest.php`
  - いいね追加/削除
  - いいね数カウント
  - 重複いいね防止
  - 未認証ユーザーエラー
  - **11テスト、30アサーション - 全テストパス**

#### 1.5 パフォーマンス最適化 ✅
- [x] N+1クエリ問題の解決（API呼び出し: 20回 → 0回）
  - `ReviewResource.php`: `likes_count` と `is_liked` を追加
  - `ReviewController.php`: eager loading で `likes` リレーションをロード
  - `LikeButton.vue`: 初期値 props が渡されている場合は API 呼び出しをスキップ
- [x] ゲストユーザーのいいね数表示対応

---

### Phase 2: 行きたいリスト機能（10.5時間）

#### 2.1 データベース準備（30分）
- [ ] マイグレーション作成: `create_wishlists_table`
- [ ] マイグレーション実行・確認

#### 2.2 バックエンド実装（3.5時間）
- [ ] `Wishlist` モデル作成
  - リレーション: `user()`, `shop()`, `sourceUser()`, `sourceReview()`
  - アクセサ: `priorityLabel` (いつか/そのうち/絶対)
- [ ] `WishlistRepository` 作成
  - `add()`: 追加
  - `remove()`: 削除
  - `updatePriority()`: 優先度変更
  - `updateStatus()`: 状態変更（行きたい→行った）
  - `getUserWishlists()`: ユーザーのリスト取得
  - `getShopStatus()`: 特定店舗の状態取得
- [ ] `WishlistController` 作成
  - `store()`: 追加
  - `destroy()`: 削除
  - `updatePriority()`: 優先度変更
  - `updateStatus()`: 状態変更
  - `index()`: リスト取得
- [ ] `ShopController` に追加
  - `wishlistStatus()`: 特定店舗の行きたい状態確認
- [ ] APIルート追加 (`routes/api.php`)
  - `POST /api/my-wishlist` → `WishlistController@store`
  - `DELETE /api/my-wishlist/{shop}` → `WishlistController@destroy`
  - `PATCH /api/my-wishlist/{shop}/priority` → `WishlistController@updatePriority`
  - `PATCH /api/my-wishlist/{shop}/status` → `WishlistController@updateStatus`
  - `GET /api/my-wishlist` → `WishlistController@index`
  - `GET /api/shops/{shop}/wishlist-status` → `ShopController@wishlistStatus`
- [ ] `Shop` モデルにリレーション追加: `wishlists()`

#### 2.3 フロントエンド実装（5時間）
- [ ] `WishlistButton.vue` コンポーネント作成
  - 追加/削除トグル
  - ローディング状態
  - 視覚的状態表示:
    - 未登録: グレー「🔖 行きたい」
    - want_to_go: 青色「🔖 行きたい」（トグル可能）
    - visited: バッジ「✓ 行った」（ボタン無効、削除不可）
  - トースト通知
- [ ] `PrioritySelector.vue` コンポーネント作成
  - 3つのボタン横並び（いつか/そのうち/絶対）
  - 選択中のみ色付き背景（灰/黄/赤）
  - 星は一切表示しない
  - タップで切り替え
- [ ] API連携 (`frontend/composables/useWishlists.ts`)
- [ ] 店舗詳細ページに統合
  - 「🔖 行きたい」ボタン配置（色変化で状態表示）
  - `status=visited` の場合: ボタン無効、削除不可
- [ ] レビューカードに「🔖 行きたい」ボタン追加
  - トグル式
  - クリック時: `source_type=review`, `source_review_id` 記録
- [ ] 行きたいリストページ実装 (`frontend/pages/my/wishlists.vue`)
  - タブ切り替え（行きたい/行った）
  - 優先度別グルーピング表示
  - `PrioritySelector` コンポーネント統合
  - ソート機能（優先度順/追加日順）
  - 「行きたい」タブ: [✓ 行った] [削除] ボタン
  - 「行った」タブ: [📝 レビューを書く] [削除] ボタン
- [ ] 「行った」後のレビュー執筆促進モーダル

#### 2.4 テスト実装（2.5時間）
- [ ] Feature Test: `WishlistTest.php`
  - 追加/削除
  - 優先度変更
  - 「行った」への変更
  - 重複追加防止
  - 出典情報記録
  - ソート機能
- [ ] Unit Test: `WishlistRepositoryTest.php`

---

## 実装順序と依存関係

```
Phase 1: いいね機能
  1. DB準備（マイグレーション）
     ↓
  2. モデル＆Repository作成
     ↓
  3. Controller＆APIルート
     ↓
  4. フロントエンド実装
     ↓
  5. テスト実装・実行

Phase 2: 行きたいリスト機能
  1. DB準備（マイグレーション）
     ↓
  2. モデル＆Repository作成
     ↓
  3. Controller＆APIルート
     ↓
  4. フロントエンド実装
     ↓
  5. テスト実装・実行
```

---

## 成功基準

### いいね機能
- [ ] レビューカードにいいねボタンが表示される
- [ ] いいね数がリアルタイムで更新される
- [ ] いいねリストページで過去のいいねを閲覧できる
- [ ] 全テストケースがパス
- [ ] エラーログにクリティカルなエラーなし

### 行きたいリスト機能
- [ ] レビュー/店舗詳細から気軽に「行きたい」追加できる
- [ ] 優先度（★3段階）の変更が直感的にできる
- [ ] 優先度順/追加日順でソートできる
- [ ] 「行った」への変更がスムーズ
- [ ] 出典情報が正しく記録される（誰のレビューから追加したか）
- [ ] 全テストケースがパス
- [ ] エラーログにクリティカルなエラーなし

---

## 将来的な拡張案

### Phase 3（オプション）: 連携・通知機能
- [ ] いいねリスト → 行きたいリスト一括追加
- [ ] 「あなたのレビューが◯人にいいねされました」通知
- [ ] 「あなたのレビューから◯人が訪問しました」通知
- [ ] 影響力スコア表示
- [ ] 他ユーザーの行きたいリスト閲覧（公開設定）

---

## リスク管理

### 想定されるトラブルと対策

1. **いいね・行きたいボタンの連打**
   - 対策: フロントエンドでローディング状態中は無効化
   - バックエンド: UNIQUE制約で重複防止

2. **パフォーマンス懸念（いいね数集計）**
   - 対策: レビュー取得時にJOINで一度に取得
   - 将来的にはカウントキャッシュ検討

3. **行きたいリストの肥大化**
   - 対策: ページネーション実装
   - 一度に表示する件数を制限

---

## ロールバック計画

問題発生時の切り戻し手順:

1. 新規APIエンドポイントを無効化
2. フロントエンドのボタンを非表示化
3. データベースのテーブルは残置（削除不要）
4. 既存機能には一切影響なし

---

## 実装スケジュール

- **Day 1（4〜5時間）**:
  - Phase 1: いいね機能の完全実装

- **Day 2（5時間）**:
  - Phase 2前半: 行きたいリスト機能のバックエンド実装

- **Day 3（5.5時間）**:
  - Phase 2後半: 行きたいリスト機能のフロントエンド実装＆テスト

**合計工数: 約14.5〜15.5時間（2日程度）**
