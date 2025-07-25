# フロントエンド実装

ユーザーフィルタ機能のフロントエンド実装タスクです。

## 実装対象

### 1. レビュー一覧ページの拡張
**ファイル**: `frontend/pages/reviews/index.vue`

#### QueryString パラメータ対応
```vue
<script setup lang="ts">
// 既存のクエリパラメータに user_id を追加
const route = useRoute()
const shopId = ref(route.query.shop_id as string || '')
const userId = ref(route.query.user_id as string || '')  // 新規追加

// API呼び出し時にパラメータ追加
const { data: reviews, pending } = await $fetch('/api/reviews', {
  query: {
    ...(shopId.value && { shop_id: shopId.value }),
    ...(userId.value && { user_id: userId.value }),  // 新規追加
    page: currentPage.value,
  }
})

// ユーザー情報取得（user_id がある場合のみ）
const { data: userInfo } = await $fetch(`/api/users/${userId.value}/info`, {
  key: `user-${userId.value}`,
  server: false,
  lazy: true,
  default: () => null,
  // user_id がない場合はスキップ
  skip: !userId.value
})
</script>
```

#### ページタイトル・UI拡張
```vue
<template>
  <div class="container mx-auto px-4 py-6">
    <!-- ページタイトル（動的） -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        <span v-if="userInfo">{{ userInfo.name }}さんのレビュー</span>
        <span v-else-if="shopInfo">{{ shopInfo.name }} のレビュー</span>
        <span v-else>レビュー一覧</span>
      </h1>
      
      <!-- ユーザー情報表示（user_id フィルタ時のみ） -->
      <div v-if="userInfo" class="bg-blue-50 p-4 rounded-lg mb-4">
        <div class="flex items-center space-x-4">
          <div class="text-sm text-gray-600">
            登録日: {{ formatDate(userInfo.created_at) }}
          </div>
          <NuxtLink 
            to="/reviews" 
            class="text-blue-600 hover:text-blue-800 text-sm"
          >
            全レビューを見る
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- 既存のレビュー一覧UI -->
    <div class="grid gap-6">
      <!-- 既存コンテンツはそのまま -->
    </div>
  </div>
</template>
```

### 2. ランキング一覧ページの拡張
**ファイル**: `frontend/pages/rankings/index.vue`

#### 同様の実装パターン
```vue
<script setup lang="ts">
const route = useRoute()
const categoryId = ref(route.query.category_id as string || '')
const userId = ref(route.query.user_id as string || '')  // 新規追加

// API呼び出し・ユーザー情報取得
// (レビューページと同様のパターン)
</script>

<template>
  <div class="container mx-auto px-4 py-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 mb-2">
        <span v-if="userInfo">{{ userInfo.name }}さんのランキング</span>
        <span v-else>ランキング一覧</span>
      </h1>
      
      <!-- ユーザー情報表示 -->
      <div v-if="userInfo" class="bg-blue-50 p-4 rounded-lg mb-4">
        <div class="flex items-center space-x-4">
          <div class="text-sm text-gray-600">
            登録日: {{ formatDate(userInfo.created_at) }}
          </div>
          <NuxtLink 
            to="/rankings" 
            class="text-blue-600 hover:text-blue-800 text-sm"
          >
            全ランキングを見る
          </NuxtLink>
        </div>
      </div>
    </div>

    <!-- 既存のランキング一覧UI -->
  </div>
</template>
```

### 3. ユーザー名リンク統一コンポーネント
**新規ファイル**: `frontend/components/UserLink.vue`

```vue
<template>
  <NuxtLink 
    :to="getUserPageUrl()" 
    class="text-blue-600 hover:text-blue-800 font-medium"
    :class="customClass"
  >
    {{ user.name }}
  </NuxtLink>
</template>

<script setup lang="ts">
interface Props {
  user: {
    id: number
    name: string
  }
  pageType: 'reviews' | 'rankings'
  customClass?: string
}

const props = defineProps<Props>()

const getUserPageUrl = () => {
  return `/${props.pageType}?user_id=${props.user.id}`
}
</script>
```

### 4. 既存ページにユーザーリンク追加

#### A. レビュー詳細ページ
**ファイル**: `frontend/pages/reviews/[id].vue`

```vue
<template>
  <div class="review-detail">
    <!-- 既存のレビュー詳細表示 -->
    <div class="review-meta">
      <div class="flex items-center space-x-2">
        <span class="text-gray-600">投稿者:</span>
        <!-- 修正前: {{ review.user.name }} -->
        <!-- 修正後: UserLinkコンポーネント使用 -->
        <UserLink 
          :user="review.user" 
          page-type="reviews"
          custom-class="text-sm"
        />
      </div>
    </div>
  </div>
</template>
```

#### B. 店舗詳細ページのレビュー一覧
**ファイル**: `frontend/pages/shops/[id]/index.vue`

```vue
<template>
  <!-- レビュー一覧部分 -->
  <div v-for="review in reviews" :key="review.id" class="review-item">
    <div class="review-header">
      <UserLink 
        :user="review.user" 
        page-type="reviews"
        custom-class="text-sm font-medium"
      />
      <!-- 既存の評価・日付等 -->
    </div>
  </div>
</template>
```

#### C. ランキング詳細ページ
**ファイル**: `frontend/pages/rankings/[id]/index.vue`

```vue
<template>
  <div class="ranking-detail">
    <div class="ranking-meta">
      <UserLink 
        :user="ranking.user" 
        page-type="rankings"
        custom-class="text-sm"
      />
      <!-- 既存のメタ情報 -->
    </div>
  </div>
</template>
```

## SEO・メタタグ対応

### 1. レビュー一覧ページ
```vue
<script setup lang="ts">
// メタタグ設定
useSeoMeta({
  title: computed(() => {
    if (userInfo.value) {
      return `${userInfo.value.name}さんのレビュー | マジキチメシ`
    }
    if (shopInfo.value) {
      return `${shopInfo.value.name}のレビュー | マジキチメシ`
    }
    return 'レビュー一覧 | マジキチメシ'
  }),
  description: computed(() => {
    if (userInfo.value) {
      return `${userInfo.value.name}さんが投稿したレビューの一覧です。`
    }
    return '吉祥寺の店舗レビュー一覧'
  })
})
</script>
```

### 2. ランキング一覧ページ
```vue
<script setup lang="ts">
useSeoMeta({
  title: computed(() => {
    if (userInfo.value) {
      return `${userInfo.value.name}さんのランキング | マジキチメシ`
    }
    return 'ランキング一覧 | マジキチメシ'
  }),
  description: computed(() => {
    if (userInfo.value) {
      return `${userInfo.value.name}さんが作成したランキングの一覧です。`
    }
    return '吉祥寺の店舗ランキング一覧'
  })
})
</script>
```

## エラーハンドリング

### 存在しないユーザーID
```vue
<script setup lang="ts">
const { data: userInfo, error: userError } = await $fetch(`/api/users/${userId.value}/info`, {
  // ... 既存設定
})

// エラー時の処理
if (userError.value?.statusCode === 404) {
  throw createError({
    statusCode: 404,
    statusMessage: 'ユーザーが見つかりません'
  })
}
</script>
```

## モバイル対応

### レスポンシブなユーザー情報表示
```vue
<template>
  <!-- ユーザー情報表示（モバイル対応） -->
  <div v-if="userInfo" class="bg-blue-50 p-3 md:p-4 rounded-lg mb-4">
    <div class="flex flex-col space-y-2 md:flex-row md:items-center md:space-y-0 md:space-x-4">
      <div class="text-sm text-gray-600">
        登録日: {{ formatDate(userInfo.created_at) }}
      </div>
      <NuxtLink 
        to="/reviews" 
        class="text-blue-600 hover:text-blue-800 text-sm underline"
      >
        全レビューを見る
      </NuxtLink>
    </div>
  </div>
</template>
```

## 完了チェックリスト

### 基本機能
- [ ] reviews/index.vue で user_id パラメータ対応
- [ ] rankings/index.vue で user_id パラメータ対応
- [ ] UserLink コンポーネント作成
- [ ] ユーザー情報表示UI追加

### リンク追加
- [ ] レビュー詳細ページにユーザーリンク
- [ ] 店舗詳細ページのレビュー一覧にユーザーリンク
- [ ] ランキング詳細ページにユーザーリンク
- [ ] レビュー一覧のユーザーリンク
- [ ] ランキング一覧のユーザーリンク

### UX・品質
- [ ] ページタイトル動的変更
- [ ] SEOメタタグ設定
- [ ] エラーハンドリング実装
- [ ] モバイルレスポンシブ対応
- [ ] 既存機能への影響確認

## 注意事項

### パフォーマンス
- ユーザー情報APIは `server: false, lazy: true` で初期表示を阻害しない
- 不要なAPIコールを避けるため `skip` 条件を適切に設定

### アクセシビリティ
- ユーザーリンクには適切な `aria-label` 検討
- フォーカス管理・キーボードナビゲーション確認