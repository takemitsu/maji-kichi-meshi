# フロントエンド：データベース構造変更対応タスク

**日付**: 2025-07-11  
**タスク種別**: 🔄 フロントエンド適応作業  
**対象**: Vue.js/Nuxt.js フロントエンド  
**担当**: 明日対応予定のClaude  

## 🎯 概要

バックエンドのデータベース構造修正（2025-07-11実施）に伴い、フロントエンド側で対応が必要な箇所をまとめます。主にランキング機能とレビュー機能の新しいデータ構造への適応が必要です。

## 📋 **対応必要な主要変更**

### 1. **ランキング機能の新データ構造対応** ⚠️ **重要**

#### 変更内容
- **旧構造**: 1店舗 = 1ランキングレコード
- **新構造**: 1ランキング = 1レコード + 複数ランキングアイテム

#### API応答形式の変化
```typescript
// 旧構造（複数レコード）
[
  {
    id: 1,
    rank_position: 1,
    title: "ラーメンランキング",
    shop: { id: 1, name: "店舗A" }
  },
  {
    id: 2, 
    rank_position: 2,
    title: "ラーメンランキング",
    shop: { id: 2, name: "店舗B" }
  }
]

// 新構造（単一レコード + shops配列）
{
  id: 1,
  title: "ラーメンランキング",
  description: "...",
  is_public: true,
  user: { id: 1, name: "..." },
  category: { id: 1, name: "..." },
  shops: [
    { id: 1, name: "店舗A", rank_position: 1, ... },
    { id: 2, name: "店舗B", rank_position: 2, ... }
  ]
}
```

#### 対応必要ファイル
- `frontend/pages/rankings/index.vue` - ランキング一覧表示
- `frontend/pages/rankings/[id]/index.vue` - ランキング詳細表示  
- `frontend/pages/rankings/[id]/edit.vue` - ランキング編集
- `frontend/pages/rankings/create.vue` - ランキング作成
- `frontend/components/ranking/` - ランキング関連コンポーネント
- `frontend/stores/ranking.js` - Piniaストア

### 2. **レビュー機能のユニーク制約削除対応** 📝

#### 変更内容
- **旧制約**: 1ユーザー × 1店舗 = 1レビューのみ
- **新制約**: 制約なし（複数回レビュー可能）

#### 対応箇所
- レビュー投稿時のバリデーション調整
- 「既にレビュー済み」UI表示の削除
- 同一店舗の複数レビュー表示対応

## 🔧 **具体的対応作業**

### Phase 1: ランキング表示の修正

#### 1.1 ランキング一覧ページ (`frontend/pages/rankings/index.vue`)
```vue
<!-- 修正前 -->
<div v-for="ranking in rankings" :key="ranking.id">
  <h3>{{ ranking.title }}</h3>
  <p>{{ ranking.rank_position }}位: {{ ranking.shop.name }}</p>
</div>

<!-- 修正後 -->
<div v-for="ranking in rankings" :key="ranking.id">
  <h3>{{ ranking.title }}</h3>
  <div v-for="shop in ranking.shops" :key="shop.id">
    <p>{{ shop.rank_position }}位: {{ shop.name }}</p>
  </div>
</div>
```

#### 1.2 ランキング詳細ページ (`frontend/pages/rankings/[id]/index.vue`)
- 単一ランキングデータの`shops`配列を適切に表示
- `rank_position`による並び順表示
- ユーザー情報・カテゴリ情報の表示

#### 1.3 ランキング編集ページ (`frontend/pages/rankings/[id]/edit.vue`)
- 既存店舗の並び替え機能
- 新店舗追加機能
- 店舗削除機能
- MAX10店舗制限の表示

### Phase 2: Piniaストアの更新

#### 2.1 ランキングストア (`frontend/stores/ranking.js`)
```javascript
// 修正前
const rankings = ref([])  // 複数レコード配列

// 修正後  
const rankings = ref([])  // 単一ランキング配列（各要素にshops配列）

// API呼び出し調整
const fetchRankings = async () => {
  const { data } = await $fetch('/api/rankings')
  rankings.value = data  // グループ化済みデータ
}

const createRanking = async (rankingData) => {
  const { data } = await $fetch('/api/rankings', {
    method: 'POST',
    body: {
      title: rankingData.title,
      description: rankingData.description,
      category_id: rankingData.category_id,
      is_public: rankingData.is_public,
      shops: rankingData.shops  // [{ shop_id: 1, position: 1 }, ...]
    }
  })
  return data  // 単一ランキングオブジェクト
}
```

### Phase 3: コンポーネントの修正

#### 3.1 ランキング表示コンポーネント
- `RankingCard.vue` - 単一ランキング表示
- `RankingList.vue` - ランキング一覧表示
- `ShopRankingItem.vue` - 個別店舗ランキング表示

#### 3.2 ランキング編集コンポーネント
- `RankingForm.vue` - ランキング作成・編集フォーム
- `ShopSelector.vue` - 店舗選択・並び替えコンポーネント

### Phase 4: レビュー機能の調整

#### 4.1 レビュー投稿制約の削除
```javascript
// 修正前（制約チェック）
const canReview = !existingReviews.some(r => r.shop_id === shopId)

// 修正後（制約削除）
const canReview = true  // 常にレビュー可能
```

#### 4.2 UI表示の調整
- 「既にレビュー済みです」メッセージの削除
- 複数レビューの表示対応

## 📊 **新しいAPI仕様**

### ランキングAPI仕様

#### GET /api/rankings
```json
{
  "data": [
    {
      "id": 1,
      "title": "ラーメンランキング",
      "description": "個人的な好みランキング",
      "is_public": true,
      "user": {
        "id": 1,
        "name": "ユーザー名",
        "email": "user@example.com"
      },
      "category": {
        "id": 1,
        "name": "ラーメン"
      },
      "shops": [
        {
          "id": 1,
          "name": "店舗A",
          "description": "美味しいラーメン店",
          "address": "東京都...",
          "rank_position": 1,
          "categories": [...],
          "images": [...]
        },
        {
          "id": 2,
          "name": "店舗B", 
          "rank_position": 2,
          ...
        }
      ],
      "created_at": "2025-07-11T00:00:00Z",
      "updated_at": "2025-07-11T00:00:00Z"
    }
  ]
}
```

#### POST /api/rankings (作成)
```json
// リクエスト
{
  "title": "新しいランキング",
  "description": "説明文",
  "category_id": 1,
  "is_public": true,
  "shops": [
    { "shop_id": 1, "position": 1 },
    { "shop_id": 2, "position": 2 },
    { "shop_id": 3, "position": 3 }
  ]
}

// レスポンス
{
  "data": {
    "id": 1,
    "title": "新しいランキング",
    "shops": [
      { "id": 1, "rank_position": 1, ... },
      { "id": 2, "rank_position": 2, ... },
      { "id": 3, "rank_position": 3, ... }
    ]
  },
  "message": "Ranking created successfully"
}
```

#### PUT /api/rankings/{id} (更新)
```json
// リクエスト
{
  "title": "更新されたランキング",
  "description": "新しい説明",
  "category_id": 1,
  "is_public": false,
  "shops": [
    { "shop_id": 2, "position": 1 },  // 順位変更
    { "shop_id": 1, "position": 2 },  // 順位変更
    { "shop_id": 4, "position": 3 }   // 新店舗追加
  ]
}

// レスポンス
{
  "data": {
    "id": 1,
    "title": "更新されたランキング", 
    "shops": [...]
  },
  "message": "Ranking updated successfully"
}
```

## 🧪 **テスト項目**

### 必須テスト項目
1. **ランキング一覧表示**
   - [ ] 複数店舗を含むランキングが正しく表示される
   - [ ] 順位が正しく表示される（1位、2位、3位...）
   - [ ] ユーザー情報・カテゴリ情報が表示される

2. **ランキング詳細表示**
   - [ ] 全店舗が順位順に表示される
   - [ ] 店舗情報（画像、説明等）が表示される
   - [ ] 公開/非公開状態が正しく表示される

3. **ランキング作成**
   - [ ] 複数店舗の選択・並び替えができる
   - [ ] MAX10店舗制限が機能する
   - [ ] 作成後に正しく詳細ページに遷移する

4. **ランキング編集**  
   - [ ] 既存店舗の順位変更ができる
   - [ ] 店舗の追加・削除ができる
   - [ ] 更新後に正しく反映される

5. **レビュー機能**
   - [ ] 同じ店舗に複数回レビューできる
   - [ ] 「既にレビュー済み」エラーが出ない
   - [ ] 複数レビューが正しく表示される

## 🚨 **注意事項**

### データ移行・互換性
- 既存のフロントエンドコードは新しいAPI仕様と互換性がない
- 段階的修正ではなく、全面的な書き換えが必要
- ローカル環境での十分なテスト必須

### パフォーマンス
- 新構造ではEager Loadingによりパフォーマンス向上
- N+1問題が解決済み
- ページネーション対応済み（21+件のテストデータ）

### エラーハンドリング
- API仕様変更によるエラーレスポンス形式確認
- 適切なエラー表示の実装

## 📁 **関連ファイル**

### 確実に修正必要
- `frontend/pages/rankings/index.vue`
- `frontend/pages/rankings/[id]/index.vue` 
- `frontend/pages/rankings/[id]/edit.vue`
- `frontend/pages/rankings/create.vue`
- `frontend/stores/ranking.js`

### 修正可能性あり
- `frontend/components/ranking/` 配下のコンポーネント
- `frontend/pages/reviews/` 配下のレビュー関連ページ
- `frontend/types/` TypeScript型定義

### 参考ファイル
- `backend/app/Http/Controllers/Api/RankingController.php` - API仕様確認
- `backend/app/Http/Resources/RankingResource.php` - レスポンス形式確認
- `backend/tests/Feature/RankingApiNormalizedTest.php` - テストケース参考

## ✅ **完了チェックリスト**

### Phase 1: 基本表示修正
- [ ] ランキング一覧ページの表示修正
- [ ] ランキング詳細ページの表示修正
- [ ] Piniaストアのデータ構造修正

### Phase 2: 機能修正
- [ ] ランキング作成機能の修正
- [ ] ランキング編集機能の修正
- [ ] レビュー制約削除対応

### Phase 3: テスト・調整
- [ ] 全画面での表示確認
- [ ] API連携テスト
- [ ] エラーハンドリング確認
- [ ] パフォーマンス確認

### Phase 4: 仕上げ
- [ ] コードレビュー
- [ ] TypeScript型定義更新
- [ ] ドキュメント更新
- [ ] 最終テスト

---

**作成者**: Claude Code  
**作成日**: 2025-07-11 14:50  
**推定作業時間**: 3-4時間  
**優先度**: 🚨 高（フロントエンド機能停止中）

**次回担当者へ**: バックエンドAPI仕様は完全に動作確認済みです。フロントエンド側の適応作業に集中してください。不明点があれば、このドキュメントの関連ファイルを参照してください。