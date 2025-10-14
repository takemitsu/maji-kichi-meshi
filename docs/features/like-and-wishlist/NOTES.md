# 実装時の注意事項

## ⚠️ 実装開始前に必ず確認すること

### 1. plan.mdを最初に読む
`docs/features/like-and-wishlist/plan.md` に全仕様が記載されています。
- DB設計
- API設計
- UI設計
- 実装タスク一覧

---

## 🎯 重要な仕様（齟齬が起きやすいポイント）

### 1. **優先度UIの仕様**
- ✅ 星は一切表示しない
- ✅ 色のみで表現（灰/黄/赤）
- ✅ データは `priority: 1/2/3`
- ⚠️ **色は仮決め**: 実装時にTailwindで実際の色を見て調整OK

```
行きたい度:
[いつか] [そのうち] [絶対]
 ↑灰色   ↑黄色    ↑赤色
         選択中
```

---

### 2. **削除動作の重要な仕様**
⚠️ **訪問記録の誤削除を防ぐ仕様**

| 場所 | status=want_to_go | status=visited |
|------|-------------------|----------------|
| 店舗詳細ページ | トグルで削除可能 | **削除不可** (バッジ表示のみ) |
| 行きたいリストページ | 削除可能 | 削除可能 |

**理由:**
- 店舗詳細ページで誤ってトグル → 訪問記録が消える事故を防ぐ
- 本当に削除したい場合は、リストページから明示的に削除

---

### 3. **トグル式API仕様**
⚠️ **DELETEエンドポイントは存在しない**

#### いいね機能
- ✅ `POST /api/reviews/{review}/like` → トグル（追加/削除を1つのエンドポイントで）
- ❌ ~~`DELETE /api/reviews/{review}/like`~~ → 存在しない

#### 行きたいリスト機能
- ✅ `POST /api/my-wishlist` → 追加
- ✅ `DELETE /api/my-wishlist/{shop}` → 削除（通常の削除）
- ⚠️ ただし、フロントエンドで `status=visited` の場合は削除ボタンを表示しない（店舗詳細ページのみ）

---

### 4. **命名規則の統一**
既存APIとの統一を確認：

| 種類 | エンドポイント | 命名パターン |
|------|---------------|-------------|
| 既存 | `/api/my-reviews` | `my-` プレフィックス |
| 既存 | `/api/my-rankings` | `my-` プレフィックス |
| 新規 | `/api/my-liked-reviews` | `my-` プレフィックス ✅ |
| 新規 | `/api/my-wishlist` | `my-` プレフィックス ✅ (単数形) |

⚠️ `/api/me/` ではなく `/api/my-` を使用

---

### 5. **文言の統一**
- ✅ 「行った」（plan.mdに記載）
- ❌ 「訪問済」「訪問済み」は使わない

---

## 📝 実装の進め方

### Phase 1: いいね機能（4〜5時間）
1. DB準備（マイグレーション）
2. バックエンド実装（Model/Repository/Controller）
3. フロントエンド実装（コンポーネント/API連携）
4. テスト実装

### Phase 2: 行きたいリスト機能（10.5時間）
1. DB準備（マイグレーション）
2. バックエンド実装
3. フロントエンド実装（特に `PrioritySelector.vue` に注意）
4. テスト実装

---

## 🐛 テスト時の確認ポイント

### いいね機能
- [ ] トグル動作（追加→削除→追加）
- [ ] いいね数カウントがリアルタイム更新
- [ ] 重複いいね防止（UNIQUE制約）

### 行きたいリスト機能
- [ ] 優先度変更が正しく動作
- [ ] `status=visited` の店舗は店舗詳細から削除できない
- [ ] 行きたいリストページからは削除できる
- [ ] 出典情報（source_user_id, source_review_id）が正しく記録される

---

## 💡 Tips

### 色の調整
plan.mdに「灰/黄/赤」とあるが、以下のようなTailwindクラスを試してみて調整：

```vue
<!-- 未選択 -->
<button class="bg-gray-200 text-gray-600">いつか</button>

<!-- 選択中 -->
<button class="bg-gray-500 text-white">いつか</button>  <!-- priority: 1 -->
<button class="bg-yellow-500 text-white">そのうち</button>  <!-- priority: 2 -->
<button class="bg-red-500 text-white">絶対</button>  <!-- priority: 3 -->
```

実際に見て、視認性・直感性を確認してから決定。

---

## 🚀 Phase 1 で学んだパフォーマンス最適化と注意点

### ⚠️ pluck() による順序喪失の問題

**問題**: Eloquent の `pluck('relation')` は IN句を使用するため、順序が保証されない

```php
// ❌ 順序が失われる
$likes = ReviewLike::orderBy('created_at', 'desc')->paginate(15);
$reviews = $likes->pluck('review');  // IN句で順序喪失

// 実行されるSQL:
// SELECT * FROM review_likes ORDER BY created_at DESC  -- 順序あり
// SELECT * FROM reviews WHERE id IN (47, 60, 58)       -- 順序なし！
```

**解決策**: `getCollection()->map()` を使用して順序を保持

```php
// ✅ 順序が保持される
$likes = ReviewLike::orderBy('created_at', 'desc')->paginate(15);
$reviews = $likes->getCollection()->map(fn ($like) => $like->review);
```

**影響**:
- いいね解除→再いいね で一番上に表示されるべきだが、表示されない
- 最新のいいねが反映されない
- ユーザー体験の悪化

### N+1 クエリ問題の解決パターン

**問題**: コンポーネント単位で個別にAPIを呼ぶと、一覧ページで大量のAPI呼び出しが発生（10〜20回）

**解決策**:
1. **バックエンド**: API Resource で関連データを含める
   ```php
   // ReviewResource.php
   'likes_count' => $this->whenLoaded('likes', function () {
       return $this->likes->count();
   }, 0),
   'is_liked' => $this->when(Auth::check() && $this->relationLoaded('likes'), function () {
       return $this->likes->contains('user_id', Auth::id());
   }, false),
   ```

2. **バックエンド**: Controller で eager loading
   ```php
   // ReviewController.php
   $query = Review::with(['user', 'shop.publishedImages', 'publishedImages', 'likes']);
   ```

3. **フロントエンド**: コンポーネントに初期値を props で渡す
   ```vue
   <!-- pages/reviews/index.vue -->
   <LikeButton
       :review-id="review.id"
       :initial-likes-count="review.likes_count"
       :initial-is-liked="review.is_liked" />
   ```

4. **フロントエンド**: コンポーネント側で初期値がある場合は API 呼び出しをスキップ
   ```typescript
   // LikeButton.vue
   onMounted(() => {
       if (props.initialLikesCount === undefined) {
           fetchLikes() // 初期値がない場合のみ API 呼び出し
       }
   })
   ```

**結果**: API呼び出し数 **20回 → 0回** に削減

### Phase 2 でもこのパターンを適用
- 行きたいリストの状態（`in_wishlist`, `priority`, `status`）も同様に最適化
- 店舗詳細ページや一覧ページで初期値を API response に含める
- コンポーネント側で初期値がある場合は個別 API 呼び出しをスキップ

---

## 📚 参考

既存の類似実装:
- ランキング並び替え機能: `frontend/components/RankingEditor.vue`
- レビュー一覧: `frontend/pages/reviews/index.vue`
- マイページ: `frontend/pages/my/rankings.vue`

---

## ✅ 実装完了チェックリスト

### Phase 1 完了チェック
- [x] plan.mdをすべて読んだ
- [x] この NOTES.md をすべて読んだ
- [x] トグル式APIの仕様を理解した
- [x] 命名規則（`my-` プレフィックス）を確認した
- [x] Phase 1（いいね機能）完了

### Phase 2 開始前チェック
- [ ] plan.mdの Phase 2 セクションを再確認
- [ ] この NOTES.md をすべて再読
- [ ] 優先度UIに星を表示しないことを理解
- [ ] `status=visited` の削除制御を理解
- [ ] Phase 1 の実装パターンを参考にする
