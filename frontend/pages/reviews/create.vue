<template>
  <div class="mx-auto max-w-3xl py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <!-- ヘッダー -->
      <div class="mb-8">
        <h1
          class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight"
        >
          レビューを作成
        </h1>
        <p class="mt-1 text-sm text-gray-500">訪問した店舗の評価とメモを記録しましょう</p>
      </div>

      <!-- エラーメッセージ -->
      <AlertMessage
        v-if="error.message"
        type="error"
        :title="error.title"
        :message="error.message"
        :retryable="error.retryable"
        @close="clearError"
        @retry="handleRetry"
      />

      <!-- フォーム -->
      <form @submit.prevent="submitReview" class="space-y-6">
        <!-- 店舗選択 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">店舗選択</h3>

          <div
            v-if="selectedShop"
            class="flex items-center justify-between p-4 bg-blue-50 rounded-lg"
          >
            <div>
              <h4 class="font-medium text-gray-900">{{ selectedShop.name }}</h4>
              <p class="text-sm text-gray-600">{{ selectedShop.address }}</p>
            </div>
            <button
              @click="clearSelectedShop"
              type="button"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>

          <div v-else class="space-y-4">
            <!-- 店舗検索 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                店舗を検索 <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model="shopSearchQuery"
                  @input="handleShopSearch"
                  type="text"
                  placeholder="店舗名で検索..."
                  class="input-field"
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
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
              </div>
              <p class="mt-1 text-sm text-gray-500">2文字以上入力すると検索が開始されます</p>
            </div>

            <!-- 検索結果 -->
            <div
              v-if="searchResults.length > 0 || searchLoading"
              class="max-h-64 overflow-y-auto border border-gray-200 rounded-md"
            >
              <!-- 検索中 -->
              <div v-if="searchLoading" class="flex items-center justify-center py-4">
                <LoadingSpinner size="sm" />
                <span class="ml-2 text-sm text-gray-600">検索中...</span>
              </div>
              <!-- 検索結果 -->
              <div
                v-for="shop in searchResults"
                :key="shop.id"
                @click="selectShop(shop)"
                class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
              >
                <h4 class="font-medium text-gray-900">{{ shop.name }}</h4>
                <p class="text-sm text-gray-600">{{ shop.address }}</p>
              </div>
            </div>

            <!-- 検索案内・結果なし -->
            <div v-if="shopSearchQuery" class="text-center py-4">
              <div v-if="shopSearchQuery.length < 2" class="text-gray-500">
                <p class="text-sm">あと{{ 2 - shopSearchQuery.length }}文字入力してください</p>
              </div>
              <div v-else-if="searchResults.length === 0 && !searchLoading" class="text-gray-500">
                <p class="text-sm mb-2">
                  「{{ shopSearchQuery }}」の検索結果が見つかりませんでした
                </p>
                <NuxtLink to="/shops" class="text-sm text-blue-600 hover:text-blue-800">
                  新しい店舗を登録する
                </NuxtLink>
              </div>
            </div>
          </div>
        </div>

        <!-- 評価 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">評価</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- 星評価 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                星評価 <span class="text-red-500">*</span>
              </label>
              <div class="flex items-center space-x-1" @keydown="handleStarKeydown">
                <button
                  v-for="star in 5"
                  :key="star"
                  @click="
                    () => {
                      form.rating = star
                      validateRating()
                    }
                  "
                  @keydown.enter.prevent="
                    () => {
                      form.rating = star
                      validateRating()
                    }
                  "
                  @keydown.space.prevent="
                    () => {
                      form.rating = star
                      validateRating()
                    }
                  "
                  type="button"
                  class="focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 rounded transition-all"
                  :aria-label="`${star}つ星を選択`"
                  :aria-pressed="form.rating === star"
                >
                  <svg
                    class="w-8 h-8 transition-colors"
                    :class="
                      star <= form.rating
                        ? 'text-yellow-400 hover:text-yellow-500'
                        : 'text-gray-300 hover:text-gray-400'
                    "
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                    ></path>
                  </svg>
                </button>
                <span class="ml-2 text-sm text-gray-600">({{ form.rating }}/5)</span>
              </div>
              <div v-if="!validation.rating.valid" class="mt-1 text-sm text-red-600">
                {{ validation.rating.message }}
              </div>
            </div>

            <!-- リピート意向 -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                リピート意向 <span class="text-red-500">*</span>
              </label>
              <div class="space-y-2">
                <label
                  v-for="option in repeatOptions"
                  :key="option.value"
                  class="flex items-center"
                >
                  <input
                    v-model="form.repeat_intention"
                    :value="option.value"
                    type="radio"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    @change="validateRepeatIntention()"
                  />
                  <span class="ml-2 text-sm text-gray-900">{{ option.label }}</span>
                </label>
              </div>
              <div v-if="!validation.repeat_intention.valid" class="mt-1 text-sm text-red-600">
                {{ validation.repeat_intention.message }}
              </div>
            </div>
          </div>
        </div>

        <!-- 訪問日 -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">訪問日</h3>
          <div class="max-w-xs">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              訪問した日付 <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.visited_at"
              type="date"
              class="input-field"
              :max="today"
              required
              @change="validateVisitedAt()"
            />
            <div v-if="!validation.visited_at.valid" class="mt-1 text-sm text-red-600">
              {{ validation.visited_at.message }}
            </div>
          </div>
        </div>

        <!-- コメント -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">コメント</h3>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2"> 感想・メモ（任意） </label>
            <textarea
              v-model="form.comment"
              rows="6"
              class="input-field"
              placeholder="味の感想、雰囲気、サービスなど、自由に記録してください..."
            ></textarea>
            <p class="mt-2 text-sm text-gray-500">{{ form.comment.length }}/1000 文字</p>
          </div>
        </div>

        <!-- 写真アップロード -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">写真</h3>
          <ImageUpload v-model="uploadedImages" :max-files="5" />
        </div>

        <!-- 送信ボタン -->
        <div class="flex items-center justify-between pt-6">
          <NuxtLink to="/reviews" class="btn-secondary"> キャンセル </NuxtLink>
          <button
            type="submit"
            :disabled="!canSubmit || submitting"
            class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <LoadingSpinner v-if="submitting" size="sm" color="white" class="mr-2" />
            {{ submitting ? '作成中...' : 'レビューを作成' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Shop } from '~/types/api'

// 認証ミドルウェア適用
definePageMeta({
  middleware: 'auth',
})

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()

// リアクティブデータ
const selectedShop = ref<Shop | null>(null)
const shopSearchQuery = ref('')
const searchResults = ref<Shop[]>([])
const searchLoading = ref(false)
const error = ref({
  message: '',
  title: '',
  retryable: false,
  retryAction: null as (() => Promise<void>) | null,
})
const submitting = ref(false)

// フォームデータ
const form = ref({
  shop_id: null as number | null,
  rating: 0,
  repeat_intention: '',
  visited_at: '',
  comment: '',
})

// バリデーション
const validation = ref({
  shop_id: { valid: true, message: '' },
  rating: { valid: true, message: '' },
  repeat_intention: { valid: true, message: '' },
  visited_at: { valid: true, message: '' },
})

// 画像アップロード
const uploadedImages = ref<File[]>([])

// リピート意向オプション
const repeatOptions = [
  { value: 'yes', label: 'また行く' },
  { value: 'maybe', label: 'わからん' },
  { value: 'no', label: '行かない' },
]

// 今日の日付（最大値として使用）
const today = new Date().toISOString().split('T')[0]

// バリデーション
const canSubmit = computed(() => {
  return (
    form.value.shop_id &&
    form.value.rating > 0 &&
    form.value.repeat_intention &&
    form.value.visited_at
  )
})

// 店舗検索
const handleShopSearch = useDebounceFn(async () => {
  if (!shopSearchQuery.value || shopSearchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  try {
    searchLoading.value = true
    const response = await $api.shops.list({ search: shopSearchQuery.value })
    searchResults.value = response.data || []
  } catch (err: unknown) {
    console.error('Failed to search shops:', err)
    searchResults.value = []
    setError({
      title: '店舗検索エラー',
      message:
        'ネットワークエラーまたはサーバーエラーが発生しました。しばらく時間をおいて再度お試しください。',
      retryable: true,
      retryAction: () => handleShopSearch(),
    })
  } finally {
    searchLoading.value = false
  }
}, 300)

// 店舗選択
const selectShop = (shop: Shop) => {
  selectedShop.value = shop
  form.value.shop_id = shop.id
  shopSearchQuery.value = ''
  searchResults.value = []
  validateShop()
}

// 店舗選択クリア
const clearSelectedShop = () => {
  selectedShop.value = null
  form.value.shop_id = null
}

// レビュー送信
const submitReview = async () => {
  if (!validateAll()) {
    setError({
      title: '入力エラー',
      message: '必須項目を入力してください。',
      retryable: false,
      retryAction: null,
    })
    return
  }

  if (!canSubmit.value) return

  try {
    submitting.value = true

    // まずレビューを作成
    const response = await $api.reviews.create(form.value)
    const reviewId = response.data.id

    // 画像がある場合はアップロード
    if (uploadedImages.value.length > 0) {
      try {
        await $api.reviews.uploadImages(reviewId, uploadedImages.value)
      } catch (imageErr) {
        console.error('Failed to upload images:', imageErr)
        // 画像アップロードに失敗してもレビューは作成されているので、警告のみ表示
        setError({
          title: '画像アップロードエラー',
          message: 'レビューは作成されましたが、画像のアップロードに失敗しました。',
          retryable: false,
          retryAction: null,
        })
      }
    }

    // 作成成功後、詳細ページに遷移
    await router.push(`/reviews/${reviewId}`)
  } catch (err: unknown) {
    console.error('Failed to create review:', err)
    if (err && typeof err === 'object' && 'response' in err) {
      const errorObj = err as { response: { status: number } }
      if (errorObj.response?.status === 422) {
        setError({
          title: '入力エラー',
          message: 'フォームの入力内容を確認してください。必須項目が未入力の可能性があります。',
          retryable: false,
          retryAction: null,
        })
      } else if (errorObj.response?.status === 401) {
        setError({
          title: '認証エラー',
          message: 'ログインが必要です。再度ログインしてください。',
          retryable: true,
          retryAction: async () => {
            await navigateTo('/login')
          },
        })
      } else {
        setError({
          title: 'レビュー作成エラー',
          message:
            'ネットワークエラーまたはサーバーエラーが発生しました。しばらく時間をおいて再度お試しください。',
          retryable: true,
          retryAction: () => submitReview(),
        })
      }
    } else {
      setError({
        title: 'レビュー作成エラー',
        message:
          'ネットワークエラーまたはサーバーエラーが発生しました。しばらく時間をおいて再度お試しください。',
        retryable: true,
        retryAction: () => submitReview(),
      })
    }
  } finally {
    submitting.value = false
  }
}

// エラーハンドリング用ヘルパー関数
const setError = (errorInfo: {
  title: string
  message: string
  retryable: boolean
  retryAction: (() => Promise<void>) | null
}) => {
  error.value = errorInfo
}

const clearError = () => {
  error.value = {
    message: '',
    title: '',
    retryable: false,
    retryAction: null,
  }
}

const handleRetry = async () => {
  if (error.value.retryAction) {
    await error.value.retryAction()
  }
}

// バリデーション関数
const validateRating = () => {
  if (form.value.rating === 0) {
    validation.value.rating = {
      valid: false,
      message: '星評価を選択してください',
    }
  } else {
    validation.value.rating = { valid: true, message: '' }
  }
}

const validateRepeatIntention = () => {
  if (!form.value.repeat_intention) {
    validation.value.repeat_intention = {
      valid: false,
      message: 'リピート意向を選択してください',
    }
  } else {
    validation.value.repeat_intention = { valid: true, message: '' }
  }
}

const validateVisitedAt = () => {
  if (!form.value.visited_at) {
    validation.value.visited_at = {
      valid: false,
      message: '訪問日を選択してください',
    }
  } else {
    validation.value.visited_at = { valid: true, message: '' }
  }
}

const validateShop = () => {
  if (!form.value.shop_id) {
    validation.value.shop_id = {
      valid: false,
      message: '店舗を選択してください',
    }
  } else {
    validation.value.shop_id = { valid: true, message: '' }
  }
}

const validateAll = () => {
  validateRating()
  validateRepeatIntention()
  validateVisitedAt()
  validateShop()

  return Object.values(validation.value).every(v => v.valid)
}

// 星評価のキーボードナビゲーション
const handleStarKeydown = (event: KeyboardEvent) => {
  if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') {
    event.preventDefault()
    form.value.rating = Math.max(1, form.value.rating - 1)
    validateRating()
  } else if (event.key === 'ArrowRight' || event.key === 'ArrowUp') {
    event.preventDefault()
    form.value.rating = Math.min(5, form.value.rating + 1)
    validateRating()
  }
}

// 初期化
onMounted(() => {
  // URLパラメータから店舗IDが指定されている場合
  if (route.query.shop_id) {
    const shopId = parseInt(route.query.shop_id as string)
    // 店舗情報を取得して設定
    loadShopById(shopId)
  }

  // デフォルトの訪問日を今日に設定
  form.value.visited_at = today
})

// 指定された店舗IDの情報を取得
const loadShopById = async (shopId: number) => {
  try {
    const response = await $api.shops.get(shopId)
    selectShop(response.data)
  } catch (err) {
    console.error('Failed to load shop:', err)
  }
}

// メタデータ設定
useHead({
  title: 'レビュー作成 - マジキチメシ',
  meta: [
    {
      name: 'description',
      content: '新しいレビューを作成して訪問記録を残しましょう',
    },
  ],
})
</script>
