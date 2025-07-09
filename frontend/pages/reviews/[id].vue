<template>
  <div class="mx-auto max-w-4xl py-6 sm:px-6 lg:px-8">
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

      <!-- レビュー詳細 -->
      <div v-if="review && !loading">
        <!-- ブレッドクラム -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-4">
            <li>
              <NuxtLink to="/reviews" class="text-gray-500 hover:text-gray-700">
                レビュー一覧
              </NuxtLink>
            </li>
            <li>
              <svg
                class="flex-shrink-0 h-5 w-5 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                  clip-rule="evenodd"
                ></path>
              </svg>
            </li>
            <li class="text-gray-900 font-medium">
              {{ review.shop?.name }} のレビュー
            </li>
          </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
          <div class="md:flex md:items-start md:justify-between">
            <div class="min-w-0 flex-1">
              <h1
                class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight"
              >
                <NuxtLink
                  :to="`/shops/${review.shop?.id}`"
                  class="hover:text-blue-600 transition-colors"
                >
                  {{ review.shop?.name }}
                </NuxtLink>
              </h1>
              <div
                class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6"
              >
                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <svg
                    class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    ></path>
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    ></path>
                  </svg>
                  {{ review.shop?.address }}
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <svg
                    class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3a4 4 0 118 0v4m-4 6a3 3 0 100-6 3 3 0 000 6zm-7 10V3a4 4 0 118 0v14a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                    ></path>
                  </svg>
                  訪問日: {{ formatDate(review.visited_at) }}
                </div>
              </div>
            </div>
            <div
              v-if="
                authStore.isLoggedIn &&
                review.user &&
                review.user.id === authStore.user?.id
              "
              class="mt-4 flex space-x-3 md:ml-4 md:mt-0"
            >
              <NuxtLink
                :to="`/reviews/${review.id}/edit`"
                class="btn-secondary"
              >
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  ></path>
                </svg>
                編集
              </NuxtLink>
              <button
                @click="deleteReview"
                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors"
              >
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"
                  ></path>
                </svg>
                削除
              </button>
            </div>
          </div>
        </div>

        <!-- レビュー内容 -->
        <div class="bg-white rounded-lg shadow">
          <!-- 評価セクション -->
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">評価</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- 星評価 -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >星評価</label
                >
                <div class="flex items-center space-x-2">
                  <div class="flex">
                    <svg
                      v-for="star in 5"
                      :key="star"
                      class="w-6 h-6"
                      :class="
                        star <= review.rating
                          ? 'text-yellow-400'
                          : 'text-gray-300'
                      "
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                      ></path>
                    </svg>
                  </div>
                  <span class="text-lg font-medium text-gray-900"
                    >{{ review.rating }}/5</span
                  >
                </div>
              </div>

              <!-- リピート意向 -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"
                  >リピート意向</label
                >
                <span
                  class="inline-flex items-center px-4 py-2 rounded-full text-base font-medium"
                  :class="{
                    'bg-green-100 text-green-800':
                      review.repeat_intention === 'yes',
                    'bg-yellow-100 text-yellow-800':
                      review.repeat_intention === 'maybe',
                    'bg-red-100 text-red-800': review.repeat_intention === 'no',
                  }"
                >
                  {{ getRepeatIntentionText(review.repeat_intention) }}
                </span>
              </div>
            </div>
          </div>

          <!-- コメントセクション -->
          <div v-if="review.comment" class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">コメント</h3>
            <div class="prose prose-sm text-gray-900">
              <p class="whitespace-pre-wrap">{{ review.comment }}</p>
            </div>
          </div>

          <!-- 画像セクション -->
          <div
            v-if="review.images && review.images.length > 0"
            class="px-6 py-4 border-b border-gray-200"
          >
            <h3 class="text-lg font-medium text-gray-900 mb-4">写真</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div
                v-for="image in review.images"
                :key="image.id"
                class="aspect-square bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition-opacity"
                @click="openImageModal(image)"
              >
                <img
                  :src="image.url"
                  :alt="`レビュー画像 ${image.id}`"
                  class="w-full h-full object-cover"
                  @error="handleImageError(image)"
                />
              </div>
            </div>
          </div>

          <!-- メタデータセクション -->
          <div class="px-6 py-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">詳細情報</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div>
                <label class="block font-medium text-gray-700"
                  >レビューID</label
                >
                <p class="text-gray-900">{{ review.id }}</p>
              </div>
              <div>
                <label class="block font-medium text-gray-700">投稿日</label>
                <p class="text-gray-900">
                  {{ formatDateTime(review.created_at) }}
                </p>
              </div>
              <div v-if="review.updated_at !== review.created_at">
                <label class="block font-medium text-gray-700">最終更新</label>
                <p class="text-gray-900">
                  {{ formatDateTime(review.updated_at) }}
                </p>
              </div>
              <div>
                <label class="block font-medium text-gray-700">店舗</label>
                <NuxtLink
                  :to="`/shops/${review.shop?.id}`"
                  class="text-blue-600 hover:text-blue-800"
                >
                  {{ review.shop?.name }}
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <!-- 関連アクション -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">関連アクション</h3>
          <div class="flex flex-wrap gap-4">
            <NuxtLink :to="`/shops/${review.shop?.id}`" class="btn-secondary">
              店舗詳細を見る
            </NuxtLink>
            <NuxtLink
              :to="`/reviews?shop_id=${review.shop?.id}`"
              class="btn-secondary"
            >
              この店舗の他のレビューを見る
            </NuxtLink>
            <template v-if="authStore.isLoggedIn">
              <NuxtLink
                :to="`/reviews/create?shop_id=${review.shop?.id}`"
                class="btn-primary"
              >
                この店舗の新しいレビューを作成
              </NuxtLink>
            </template>
            <template v-else>
              <NuxtLink to="/login" class="btn-primary">
                ログインしてレビューを作成
              </NuxtLink>
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- 画像モーダル（今後実装） -->
    <!-- <ImageModal v-if="selectedImage" :image="selectedImage" @close="closeImageModal" /> -->
  </div>
</template>

<script setup lang="ts">
// レビュー詳細閲覧はログイン不要、編集・削除時にログインチェック

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()
const authStore = useAuthStore()

// リアクティブデータ
const review = ref<any>(null)
const loading = ref(true)
const error = ref('')
const selectedImage = ref<any>(null)

const reviewId = computed(() => parseInt(route.params.id as string))

// レビューデータ取得
const loadReview = async () => {
  try {
    loading.value = true
    const response = await $api.reviews.get(reviewId.value)
    review.value = response.data
  } catch (err: any) {
    console.error('Failed to load review:', err)
    if (err.response?.status === 404) {
      error.value = 'レビューが見つかりませんでした'
    } else {
      error.value = 'レビューデータの取得に失敗しました'
    }
  } finally {
    loading.value = false
  }
}

// レビュー削除
const deleteReview = async () => {
  if (
    !confirm(
      `「${review.value.shop?.name}」のレビューを削除しますか？この操作は元に戻せません。`
    )
  ) {
    return
  }

  try {
    await $api.reviews.delete(reviewId.value)
    await router.push('/reviews')
  } catch (err) {
    console.error('Failed to delete review:', err)
    error.value = 'レビューの削除に失敗しました'
  }
}

// 画像モーダル
const openImageModal = (image: any) => {
  selectedImage.value = image
}

const closeImageModal = () => {
  selectedImage.value = null
}

// 画像エラーハンドリング
const handleImageError = (image: any) => {
  console.error('Failed to load image:', image.url)
  // 画像が読み込めない場合の処理（プレースホルダー表示など）
}

// ユーティリティ関数
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('ja-JP')
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

// 初期化
onMounted(async () => {
  await loadReview()
})

// メタデータ設定
useHead(() => ({
  title: review.value
    ? `${review.value.shop?.name} のレビュー - マジキチメシ`
    : 'レビュー詳細 - マジキチメシ',
  meta: [
    {
      name: 'description',
      content: review.value
        ? `${review.value.shop?.name}のレビュー詳細`
        : 'レビュー詳細ページ',
    },
  ],
}))
</script>
