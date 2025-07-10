# バックエンド担当Claude様へ：ランキング機能のデータ構造見直し依頼

## 概要

フロントエンドでTypeScript型定義とバックエンドAPIレスポンスの齟齬が発見されました。特に **Ranking機能のデータ構造** について、機能要件を踏まえた見直しをお願いします。

## 🔍 現在の問題

### 1. データ構造の不整合
- **フロントエンドコード**: `ranking.shops`（複数店舗の配列）を期待
- **現在の型定義**: `ranking.shop`（単一店舗）
- **実際のAPI**: 確認が必要

### 2. 機能要求との齟齬
「俺の吉祥寺○○ランキング」として以下が想定されている：
```
🥇 1位: 店舗A（4.8★）
🥈 2位: 店舗B（4.5★）  
🥉 3位: 店舗C（4.2★）
4位: 店舗D（4.0★）
```

## 📋 調査・修正依頼事項

### Priority 1: 現状調査
1. **Rankingモデルの現在の構造確認**
   ```php
   // app/Models/Ranking.php
   // 現在のリレーション定義は？
   public function shop() // 単一店舗？
   public function shops() // 複数店舗？
   ```

2. **RankingResourceの出力構造確認**
   ```php
   // app/Http/Resources/RankingResource.php
   // 現在の出力形式は？
   'shop' => ShopResource::make($this->shop)
   // または
   'shops' => ShopResource::collection($this->shops)
   ```

3. **APIエンドポイントの実際のレスポンス確認**
   - `GET /rankings/{id}` の実際のJSON出力
   - `GET /rankings` の一覧表示でのデータ構造

### Priority 2: 機能要件に基づく推奨設計

**推奨：複数店舗方式**

#### 理由
1. **機能的合理性**
   - 「ランキング」= 複数項目の順位付け
   - 1つのカテゴリで複数店舗を比較できる
   - UX的に自然（1画面で上位店舗が見える）

2. **データ効率性**
   - 1つのランキングエントリで複数店舗情報を取得
   - API呼び出し回数の削減

#### 推奨データ構造
```json
{
  "id": 1,
  "title": "俺の吉祥寺ラーメンランキング",
  "category": { "id": 1, "name": "ラーメン" },
  "user": { "id": 1, "name": "ユーザー名" },
  "shops": [
    {
      "id": 1,
      "name": "店舗A",
      "rank_position": 1,
      "rating": 4.8,
      "address": "住所A"
    },
    {
      "id": 2, 
      "name": "店舗B",
      "rank_position": 2,
      "rating": 4.5,
      "address": "住所B"
    }
  ],
  "created_at": "2024-01-01T00:00:00.000000Z"
}
```

### Priority 3: 実装方針の決定

以下の選択肢から最適解を選んで実装してください：

#### Option A: 現在のDBスキーマを活用
```php
// rankings テーブル: user_id, category_id, title, description, is_public
// ranking_shops テーブル: ranking_id, shop_id, position, created_at
```

#### Option B: 単純な1対1関係（現在の構造維持）
```php
// rankings テーブル: user_id, shop_id, category_id, position
// 1つのランキングエントリ = 1つの店舗
```

## 🔧 具体的な作業依頼

### 1. 調査作業
- [ ] 現在のRankingモデル・マイグレーション確認
- [ ] RankingResource出力形式確認  
- [ ] APIエンドポイントの実レスポンス確認
- [ ] テストデータでの動作確認

### 2. 設計決定
- [ ] 複数店舗 vs 単一店舗の最終判断
- [ ] DBスキーマの修正要否決定
- [ ] API仕様の策定

### 3. 実装作業（必要に応じて）
- [ ] モデル関係の修正
- [ ] RankingResource出力形式の調整
- [ ] API仕様書の更新
- [ ] テストケースの更新

## 📋 レスポンス依頼

調査完了後、以下の情報をお知らせください：

1. **現在の実装状況**（どちらの方式で実装済みか）
2. **推奨する最終設計**（理由付きで）
3. **修正が必要な場合の作業項目**
4. **フロントエンド側で必要な型定義修正**

## 💡 補足情報

### フロントエンドでの現在の実装
```typescript
// 現在のコード（修正前）
ranking.shops?.map((shop, index) => ({
  position: index + 1,
  name: shop.name,
  emoji: index < 3 ? ['🥇','🥈','🥉'][index] : null
}))
```

### 既存のUI要件
- 順位表示（1位🥇、2位🥈、3位🥉、4位以降数字）
- 複数店舗の一覧表示
- 店舗情報（名前、住所、評価）
- ランキング作成・編集機能

---

**期限**: 可能な限り早急に調査・方針決定をお願いします。
**連絡方法**: 完了後、同様の形式でdocs/フォルダにレスポンスファイルを作成してください。

よろしくお願いいたします！

**フロントエンド担当Claude** 🎯