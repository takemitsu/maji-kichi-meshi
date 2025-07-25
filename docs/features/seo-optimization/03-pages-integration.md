# タスク03: 各ページでのSEOメタデータ統合

## 概要
全対象ページで`useSeoMeta` composableを使用してSEOメタデータを設定し、既存の`useHead()`実装を置き換える。

## 実装対象ページ

### 1. トップページ
- **ファイル**: `pages/index.vue`
- **メタデータ**: サイト概要、主要機能紹介
- **構造化データ**: WebSite schema

### 2. 店舗関連ページ
- **ファイル**: `pages/shops/index.vue`, `pages/shops/[id]/index.vue`
- **メタデータ**: 店舗情報、住所、評価
- **構造化データ**: Restaurant schema

### 3. レビュー関連ページ
- **ファイル**: `pages/reviews/index.vue`, `pages/reviews/[id]/index.vue`
- **メタデータ**: レビュー内容、店舗情報
- **構造化データ**: Review schema

### 4. ランキング関連ページ
- **ファイル**: `pages/rankings/index.vue`, `pages/rankings/[id]/index.vue`, `pages/rankings/public.vue`
- **メタデータ**: ランキング情報、カテゴリ
- **構造化データ**: ItemList schema

## 実装例

### Before (既存実装)
```typescript
// 現在の実装
useHead({
  title: 'マジキチメシ - 吉祥寺地域の個人的な店舗ランキング',
  meta: [
    { name: 'description', content: '...' }
  ]
})
```

### After (新実装)
```typescript
// 新しい実装
const { generateSeoMeta, generateJsonLd } = useSeoMeta()

useHead({
  ...generateSeoMeta({
    title: 'マジキチメシ - 吉祥寺地域の個人的な店舗ランキング',
    description: '吉祥寺地域の店舗について、客観的なレビューとは独立した個人的で主観的なランキングを作成・共有するアプリ',
    image: '/og-image.jpg',
    type: 'website'
  }),
  ...generateJsonLd({
    '@type': 'WebSite',
    name: 'マジキチメシ',
    url: baseUrl,
    description: '吉祥寺地域の個人的な店舗ランキング作成・共有アプリ'
  })
})
```

### 具体的なページ実装例

#### 1. 店舗詳細ページ (`pages/shops/[id]/index.vue`)
```vue
<script setup lang="ts">
// 既存のコード...
const { generateSeoMeta, generateJsonLd } = useSeoMeta()

// 店舗データ取得後
watchEffect(() => {
  if (shop.value) {
    useHead({
      ...generateSeoMeta({
        title: `${shop.value.name} - マジキチメシ`,
        description: `${shop.value.name}の詳細情報、レビュー、ランキングを確認できます。住所: ${shop.value.address}`,
        image: shop.value.image_url,
        type: 'article'
      }),
      ...generateJsonLd({
        '@type': 'Restaurant',
        name: shop.value.name,
        address: {
          '@type': 'PostalAddress',
          streetAddress: shop.value.address,
          addressLocality: '吉祥寺'
        },
        aggregateRating: shop.value.average_rating ? {
          '@type': 'AggregateRating',
          ratingValue: shop.value.average_rating,
          reviewCount: shop.value.reviews_count
        } : undefined
      })
    })
  }
})
</script>
```

#### 2. レビュー詳細ページ (`pages/reviews/[id]/index.vue`)
```vue
<script setup lang="ts">
const { generateSeoMeta, generateJsonLd } = useSeoMeta()

watchEffect(() => {
  if (review.value) {
    useHead({
      ...generateSeoMeta({
        title: `${review.value.shop?.name}のレビュー - マジキチメシ`,
        description: `${review.value.shop?.name}のレビュー。評価: ${review.value.rating}/5 - ${review.value.memo || '詳細なレビューをご覧ください'}`,
        image: review.value.images?.[0]?.urls?.medium,
        type: 'article'
      }),
      ...generateJsonLd({
        '@type': 'Review',
        reviewRating: {
          '@type': 'Rating',
          ratingValue: review.value.rating,
          bestRating: 5
        },
        author: {
          '@type': 'Person',
          name: review.value.user?.name
        },
        itemReviewed: {
          '@type': 'Restaurant',
          name: review.value.shop?.name,
          address: review.value.shop?.address
        }
      })
    })
  }
})
</script>
```

#### 3. 公開ランキングページ (`pages/rankings/public.vue`)
```vue
<script setup lang="ts">
const { generateSeoMeta, generateJsonLd } = useSeoMeta()

useHead({
  ...generateSeoMeta({
    title: '公開ランキング - マジキチメシ',
    description: 'みんなが公開している吉祥寺の店舗ランキング一覧。様々なカテゴリの個人的ランキングをチェック',
    type: 'website'
  }),
  ...generateJsonLd({
    '@type': 'ItemList',
    name: '公開ランキング一覧',
    description: '吉祥寺の店舗に関する公開ランキング',
    numberOfItems: totalItems.value
  })
})
</script>
```

## ページ固有の実装要件

### 店舗詳細ページ
- 店舗画像をOG画像として使用
- Restaurant schemaで構造化データ
- 営業時間、住所、評価情報

### レビュー詳細ページ
- レビュー画像をOG画像として使用
- Review schemaで構造化データ
- 評価、訪問日、店舗情報

### ランキングページ
- ランキング概要をdescriptionに
- ItemList schemaで上位店舗情報
- カテゴリ情報、更新日

## テスト要件
- [ ] 各ページでメタデータが適切に設定されることを確認
- [ ] Open Graphデバッガーでの表示確認
- [ ] 構造化データテストツールでの検証
- [ ] モバイル・デスクトップでの表示確認

## 注意点
- 動的データ（店舗名等）の取得タイミングに注意
- 画像URLは絶対パスで指定
- エラー時のフォールバック値を設定
- ページローディング中の表示を考慮

## 参考情報
- [Facebook Open Graph Debugger](https://developers.facebook.com/tools/debug/)
- [Twitter Card Validator](https://cards-dev.twitter.com/validator)
- [Google Rich Results Test](https://search.google.com/test/rich-results)

## 完了基準
- [ ] 全対象ページでの`useSeoMeta`実装完了
- [ ] 各ページのメタデータ設定確認完了
- [ ] 構造化データ検証ツールでのテスト完了
- [ ] SNS共有プレビューでの表示確認完了