<template>
  <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <!-- ヘッダー -->
      <div class="mb-8">
        <div class="md:flex md:items-center md:justify-between">
          <div class="min-w-0 flex-1">
            <h1
              class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight"
            >
              {{ authStore.isLoggedIn ? 'レビュー管理' : 'レビュー一覧' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500">
              {{
                authStore.isLoggedIn
                  ? 'あなたの訪問記録とレビューを管理できます'
                  : 'みんなの訪問記録とレビューを見ることができます'
              }}
            </p>
          </div>
          <div v-if="authStore.isLoggedIn" class="mt-4 flex md:ml-4 md:mt-0">
            <NuxtLink to="/reviews/create" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                ></path>
              </svg>
              レビューを作成
            </NuxtLink>
          </div>
          <div v-else class="mt-4 flex md:ml-4 md:mt-0">
            <span class="text-sm text-gray-500 px-3 py-2">
              レビューを作成するにはログインが必要です
            </span>
          </div>
        </div>
      </div>

      <!-- フィルター -->
      <div class="mb-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- 検索 -->
          <div class="md:col-span-2">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg
                  class="h-5 w-5 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  ></path>
                </svg>
              </div>
              <input
                v-model="searchQuery"
                @input="handleSearch"
                type="text"
                placeholder="店舗名やコメントで検索..."
                class="w-full py-2 pr-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- 評価フィルター -->
          <div>
            <select v-model="selectedRating" @change="handleFilter" class="input-field">
              <option value="">全ての評価</option>
              <option value="5">★★★★★ (5)</option>
              <option value="4">★★★★☆ (4)</option>
              <option value="3">★★★☆☆ (3)</option>
              <option value="2">★★☆☆☆ (2)</option>
              <option value="1">★☆☆☆☆ (1)</option>
            </select>
          </div>

          <!-- リピート意向フィルター -->
          <div>
            <select v-model="selectedRepeatIntention" @change="handleFilter" class="input-field">
              <option value="">リピート意向</option>
              <option value="yes">また行く</option>
              <option value="maybe">わからん</option>
              <option value="no">行かない</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ローディング -->
      <LoadingSpinner v-if="loading" />

      <!-- 検索/フィルタリング中 -->
      <div v-if="searchLoading && !loading" class="flex items-center justify-center py-4">
        <LoadingSpinner size="sm" />
        <span class="ml-2 text-sm text-gray-600">検索中...</span>
      </div>

      <!-- エラーメッセージ -->
      <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

      <!-- レビュー一覧 -->
      <div v-if="!loading && reviews.length > 0" class="space-y-6">
        <div
          v-for="review in reviews"
          :key="review.id"
          class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200 cursor-pointer"
          @click="navigateToReview(review)"
        >
          <div class="p-6">
            <!-- ヘッダー部分 -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex items-start space-x-4">
                <!-- 店舗画像 -->
                <div class="w-16 h-16 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                  <template v-if="review.shop?.image_url">
                    <img
                      :src="review.shop.image_url"
                      :alt="review.shop.name"
                      class="w-full h-full object-cover"
                      @error="handleShopImageError"
                    />
                  </template>
                  <template v-else>
                    <div class="w-full h-full flex items-center justify-center">
                      <svg
                        class="w-8 h-8 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m-2 0h2m0 0h4"
                        ></path>
                      </svg>
                    </div>
                  </template>
                </div>

                <div class="flex-1 min-w-0">
                  <h3 class="text-lg font-semibold text-gray-900">
                    <NuxtLink
                      :to="`/shops/${review.shop?.id}`"
                      class="hover:text-blue-600 transition-colors"
                      @click.stop
                    >
                      {{ review.shop?.name }}
                    </NuxtLink>
                  </h3>
                  <p class="text-sm text-gray-500 mt-1">
                    {{ review.shop?.address }}
                  </p>
                  <div class="flex items-center space-x-4 mt-2">
                    <span class="text-sm text-gray-500">
                      訪問日: {{ formatDate(review.visited_at) }}
                    </span>
                    <span class="text-sm text-gray-500">
                      投稿日: {{ formatDate(review.created_at) }}
                    </span>
                    <span v-if="review.user" class="text-sm text-gray-500">
                      投稿者: {{ review.user.name }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- アクションメニュー -->
              <div class="flex items-center space-x-2">
                <template
                  v-if="
                    authStore.isLoggedIn && review.user && review.user.id === authStore.user?.id
                  "
                >
                  <NuxtLink
                    :to="`/reviews/${review.id}/edit`"
                    class="text-sm text-blue-600 hover:text-blue-800"
                    @click.stop
                  >
                    編集
                  </NuxtLink>
                  <button
                    @click.stop="deleteReview(review)"
                    class="text-sm text-red-600 hover:text-red-800"
                  >
                    削除
                  </button>
                </template>
              </div>
            </div>

            <!-- 評価部分 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
              <!-- 星評価 -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">星評価</label>
                <div class="flex items-center space-x-1">
                  <div class="flex">
                    <svg
                      v-for="star in 5"
                      :key="star"
                      class="w-5 h-5"
                      :class="star <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                      ></path>
                    </svg>
                  </div>
                  <span class="text-sm text-gray-600 ml-2">({{ review.rating }}/5)</span>
                </div>
              </div>

              <!-- リピート意向 -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">リピート意向</label>
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                  :class="{
                    'bg-green-100 text-green-800': review.repeat_intention === 'yes',
                    'bg-yellow-100 text-yellow-800': review.repeat_intention === 'maybe',
                    'bg-red-100 text-red-800': review.repeat_intention === 'no',
                  }"
                >
                  {{ getRepeatIntentionText(review.repeat_intention) }}
                </span>
              </div>
            </div>

            <!-- コメント -->
            <div v-if="review.comment" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">コメント</label>
              <p class="text-gray-900 text-sm leading-relaxed">
                {{ review.comment }}
              </p>
            </div>

            <!-- 画像 -->
            <div v-if="review.images && review.images.length > 0" class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">写真</label>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <div
                  v-for="image in review.images.slice(0, 4)"
                  :key="image.id"
                  class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition-opacity"
                  @click.stop="openImageModal(image)"
                >
                  <img
                    :src="image.urls.thumbnail"
                    :alt="`レビュー画像 ${image.id}`"
                    class="w-full h-full object-cover"
                    @error="handleReviewImageError(image)"
                  />
                </div>
              </div>
              <div v-if="review.images.length > 4" class="mt-2 text-sm text-gray-500">
                他{{ review.images.length - 4 }}枚の画像があります
              </div>
            </div>

            <!-- フッター -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <div class="flex items-center space-x-4 text-sm text-gray-500">
                <span v-if="review.updated_at !== review.created_at">
                  更新: {{ formatDate(review.updated_at) }}
                </span>
              </div>
              <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500">クリックで詳細を見る →</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ページネーション -->
        <div v-if="totalPages > 1" class="flex justify-center">
          <PaginationComponent
            :current-page="currentPage"
            :total-pages="totalPages"
            :total-items="totalItems"
            :per-page="perPage"
            @page-change="handlePageChange"
          />
        </div>
      </div>

      <!-- 空の状態 -->
      <div v-if="!loading && reviews.length === 0" class="text-center py-12">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
          ></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">レビューがありません</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{
            searchQuery || selectedRating || selectedRepeatIntention
              ? '検索条件に一致するレビューが見つかりませんでした。'
              : authStore.isLoggedIn
                ? '最初のレビューを作成してみましょう。'
                : 'まだレビューがありません。'
          }}
        </p>
        <div v-if="authStore.isLoggedIn" class="mt-6">
          <NuxtLink to="/reviews/create" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              ></path>
            </svg>
            レビューを作成
          </NuxtLink>
        </div>
        <div v-else class="mt-6">
          <NuxtLink to="/login" class="btn-primary"> ログインしてレビューを作成 </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Review, ReviewImage } from '~/types/api'

// レビュー閲覧はログイン不要、作成・編集時にログインチェック

const route = useRoute()
const { $api } = useNuxtApp()
const authStore = useAuthStore()

// リアクティブデータ
const reviews = ref<Review[]>([])
const loading = ref(true)
const searchLoading = ref(false)
const error = ref('')
const searchQuery = ref('')
const selectedRating = ref('')
const selectedRepeatIntention = ref('')
const selectedImage = ref<ReviewImage | null>(null)

// ページネーション
const currentPage = ref(1)
const perPage = ref(20)
const totalItems = ref(0)
const totalPages = ref(0)

// URLパラメータから初期値を設定
onMounted(() => {
  if (route.query.shop_id) {
    // 特定の店舗のレビューを表示する場合
    searchQuery.value = (route.query.shop_name as string) || ''
  }
})

// 検索とフィルター
const handleSearch = useDebounceFn(() => {
  currentPage.value = 1 // 検索時は1ページ目に戻る
  searchLoading.value = true
  loadReviews().finally(() => {
    searchLoading.value = false
  })
}, 300)

const handleFilter = () => {
  currentPage.value = 1 // フィルター変更時は1ページ目に戻る
  searchLoading.value = true
  loadReviews().finally(() => {
    searchLoading.value = false
  })
}

// ページ変更
const handlePageChange = (page: number) => {
  currentPage.value = page
  loadReviews()
}

// レビューデータ取得
const loadReviews = async () => {
  try {
    loading.value = true

    const params: Record<string, string | number> = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (searchQuery.value) params.search = searchQuery.value
    if (selectedRating.value) params.rating = selectedRating.value
    if (selectedRepeatIntention.value) params.repeat_intention = selectedRepeatIntention.value
    if (route.query.shop_id) params.shop_id = String(route.query.shop_id)

    const response = await $api.reviews.list(params)

    // ページネーション対応のレスポンス処理
    reviews.value = response.data || []

    if (response.meta) {
      currentPage.value = response.meta.current_page
      perPage.value = response.meta.per_page
      totalItems.value = response.meta.total
      totalPages.value = response.meta.last_page
    }
  } catch (err) {
    console.error('Failed to load reviews:', err)
    error.value = 'レビューデータの取得に失敗しました'
  } finally {
    loading.value = false
  }
}

// レビュー削除
const deleteReview = async (review: Review) => {
  if (!confirm(`「${review.shop?.name}」のレビューを削除しますか？この操作は元に戻せません。`)) {
    return
  }

  try {
    await $api.reviews.delete(review.id)
    await loadReviews()
  } catch (err) {
    console.error('Failed to delete review:', err)
    error.value = 'レビューの削除に失敗しました'
  }
}

// ユーティリティ関数
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}

const getRepeatIntentionText = (intention: string) => {
  switch (intention) {
    case 'yes':
      return 'また行く'
    case 'maybe':
      return 'わからん'
    case 'no':
      return '行かない'
    default:
      return '未設定'
  }
}

// 画像モーダル制御
const openImageModal = (image: ReviewImage) => {
  selectedImage.value = image
  // 将来的にはモーダルコンポーネントを表示
}

// レビュー詳細ページに遷移
const navigateToReview = (review: Review) => {
  navigateTo(`/reviews/${review.id}`)
}

// 画像エラーハンドリング
const handleShopImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'
}

const handleReviewImageError = (_image: ReviewImage) => {
  // 画像読み込みエラーの処理（将来的に代替画像表示など）
}

// 初期化
onMounted(async () => {
  await loadReviews()
})

// メタデータ設定
useHead({
  title: 'レビュー管理 - マジキチメシ',
  meta: [{ name: 'description', content: '訪問記録とレビューの管理ページ' }],
})
</script>
