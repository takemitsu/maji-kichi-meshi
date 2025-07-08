<template>
  <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <!-- ローディング -->
      <LoadingSpinner v-if="loading" fullscreen />

      <!-- エラーメッセージ -->
      <AlertMessage
        v-if="error"
        type="error"
        :message="error"
        @close="error = ''"
      />

      <!-- 店舗詳細 -->
      <div v-if="shop && !loading">
        <!-- ブレッドクラム -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-4">
            <li>
              <NuxtLink to="/shops" class="text-gray-500 hover:text-gray-700">
                店舗一覧
              </NuxtLink>
            </li>
            <li>
              <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
              </svg>
            </li>
            <li class="text-gray-900 font-medium">
              {{ shop.name }}
            </li>
          </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
          <div class="md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
              <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                {{ shop.name }}
              </h1>
              <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  {{ shop.address }}
                </div>
                <div v-if="shop.phone" class="mt-2 flex items-center text-sm text-gray-500">
                  <svg class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                  </svg>
                  {{ shop.phone }}
                </div>
              </div>
            </div>
            <div class="mt-4 flex space-x-3 md:ml-4 md:mt-0">
              <button
                @click="editShop"
                class="btn-secondary"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                編集
              </button>
              <button
                @click="addReview"
                class="btn-primary"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                レビューを追加
              </button>
            </div>
          </div>
        </div>

        <!-- 店舗情報カード -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <!-- 基本情報 -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">基本情報</h3>
              
              <!-- 画像プレースホルダー -->
              <div class="mb-6 h-64 bg-gray-200 rounded-lg flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>

              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700">店舗名</label>
                  <p class="mt-1 text-sm text-gray-900">{{ shop.name }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700">住所</label>
                  <p class="mt-1 text-sm text-gray-900">{{ shop.address }}</p>
                </div>
                
                <div v-if="shop.phone">
                  <label class="block text-sm font-medium text-gray-700">電話番号</label>
                  <p class="mt-1 text-sm text-gray-900">{{ shop.phone }}</p>
                </div>
                
                <div v-if="shop.website">
                  <label class="block text-sm font-medium text-gray-700">ウェブサイト</label>
                  <a 
                    :href="shop.website" 
                    target="_blank"
                    class="mt-1 text-sm text-blue-600 hover:text-blue-800"
                  >
                    {{ shop.website }}
                  </a>
                </div>
                
                <div v-if="shop.description">
                  <label class="block text-sm font-medium text-gray-700">説明</label>
                  <p class="mt-1 text-sm text-gray-900">{{ shop.description }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- サイドバー情報 -->
          <div class="space-y-6">
            <!-- カテゴリ -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">カテゴリ</h3>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="category in shop.categories"
                  :key="category.id"
                  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
                >
                  {{ category.name }}
                </span>
              </div>
            </div>

            <!-- 統計情報 -->
            <div class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">統計情報</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">レビュー数</span>
                  <span class="text-sm font-medium text-gray-900">{{ shop.reviews_count || 0 }}件</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">平均評価</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{ shop.average_rating ? `${shop.average_rating.toFixed(1)}★` : '未評価' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">登録日</span>
                  <span class="text-sm font-medium text-gray-900">{{ formatDate(shop.created_at) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">最終更新</span>
                  <span class="text-sm font-medium text-gray-900">{{ formatDate(shop.updated_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Google Places情報 -->
            <div v-if="shop.google_places_id" class="bg-white rounded-lg shadow p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Google Places</h3>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Places ID</span>
                  <span class="text-sm font-mono text-gray-900 truncate">{{ shop.google_places_id }}</span>
                </div>
                <a
                  :href="`https://www.google.com/maps/place/?q=place_id:${shop.google_places_id}`"
                  target="_blank"
                  class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800"
                >
                  Google Mapsで開く
                  <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                  </svg>
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- レビュー一覧（簡易版） -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-900">最近のレビュー</h3>
              <NuxtLink
                :to="`/reviews?shop_id=${shop.id}`"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                全てのレビューを見る
              </NuxtLink>
            </div>
          </div>
          
          <div v-if="recentReviews.length > 0" class="divide-y divide-gray-200">
            <div
              v-for="review in recentReviews"
              :key="review.id"
              class="px-6 py-4"
            >
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700">
                      {{ review.user?.name?.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-2">
                    <p class="text-sm font-medium text-gray-900">{{ review.user?.name }}</p>
                    <div class="flex items-center">
                      <span class="text-sm text-yellow-400">★</span>
                      <span class="text-sm text-gray-600">{{ review.rating }}</span>
                    </div>
                    <span class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</span>
                  </div>
                  <p v-if="review.comment" class="mt-1 text-sm text-gray-700">
                    {{ review.comment }}
                  </p>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="px-6 py-8 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">レビューがありません</h3>
            <p class="mt-1 text-sm text-gray-500">この店舗の最初のレビューを書いてみましょう。</p>
            <div class="mt-6">
              <button
                @click="addReview"
                class="btn-primary"
              >
                レビューを追加
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 認証ミドルウェア適用
definePageMeta({
  middleware: 'auth'
})

const route = useRoute()
const { $api } = useNuxtApp()

// リアクティブデータ
const shop = ref<any>(null)
const recentReviews = ref<any[]>([])
const loading = ref(true)
const error = ref('')

const shopId = computed(() => parseInt(route.params.id as string))

// 店舗データ取得
const loadShop = async () => {
  try {
    loading.value = true
    const response = await $api.shops.get(shopId.value)
    shop.value = response.data
  } catch (err: any) {
    console.error('Failed to load shop:', err)
    if (err.response?.status === 404) {
      error.value = '店舗が見つかりませんでした'
    } else {
      error.value = '店舗データの取得に失敗しました'
    }
  } finally {
    loading.value = false
  }
}

// レビューデータ取得
const loadRecentReviews = async () => {
  try {
    const response = await $api.reviews.list({ shop_id: shopId.value })
    recentReviews.value = (response.data || []).slice(0, 3) // 最新3件
  } catch (err) {
    console.error('Failed to load reviews:', err)
  }
}

// アクション
const editShop = () => {
  // 編集モーダルまたは編集ページへ遷移（今後実装）
  console.log('Edit shop:', shop.value)
}

const addReview = () => {
  // レビュー追加ページへ遷移（今後実装）
  navigateTo(`/reviews/create?shop_id=${shopId.value}`)
}

// ユーティリティ関数
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}

// 初期化
onMounted(async () => {
  await Promise.all([
    loadShop(),
    loadRecentReviews()
  ])
})

// メタデータ設定
useHead(() => ({
  title: shop.value ? `${shop.value.name} - 店舗詳細 - マジキチメシ` : '店舗詳細 - マジキチメシ',
  meta: [
    { name: 'description', content: shop.value ? `${shop.value.name}の詳細情報とレビュー` : '店舗詳細ページ' }
  ]
}))
</script>