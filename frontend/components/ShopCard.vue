<template>
  <div
    class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden cursor-pointer touch-manipulation"
    @click="navigateToShop"
    @touchstart="handleTouchStart"
    @touchend="handleTouchEnd"
  >
    <!-- 店舗画像 -->
    <div ref="imageContainer" class="relative h-48 bg-gray-200 overflow-hidden">
      <template v-if="shop.image_url && shouldLoadImage">
        <img
          :src="shop.image_url"
          :alt="shop.name"
          class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
          @error="handleImageError"
          @load="handleImageLoad"
        />
        <div
          v-if="imageLoading"
          class="absolute inset-0 bg-gray-200 flex items-center justify-center"
        >
          <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"
          ></div>
        </div>
      </template>
      <template v-else>
        <div class="w-full h-full flex items-center justify-center">
          <svg
            class="w-12 h-12 text-gray-400"
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

    <!-- 店舗情報 -->
    <div class="p-6">
      <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
          <h3
            class="text-lg font-semibold text-gray-900 truncate hover:text-blue-600 transition-colors"
          >
            <span
              v-if="shop.highlightedName"
              v-html="shop.highlightedName"
            ></span>
            <span v-else>{{ shop.name }}</span>
          </h3>
          <p class="text-sm text-gray-500 mt-1">
            <span
              v-if="shop.highlightedAddress"
              v-html="shop.highlightedAddress"
            ></span>
            <span v-else>{{ shop.address }}</span>
          </p>
        </div>

        <!-- アクションメニュー（認証済みユーザーのみ） -->
        <div v-if="showActions && authStore.isLoggedIn" class="ml-4 relative">
          <button
            @click.stop="toggleActionMenu"
            class="text-gray-400 hover:text-gray-600 focus:outline-none touch-manipulation p-2 -m-2"
          >
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path
                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"
              ></path>
            </svg>
          </button>

          <!-- ドロップダウンメニュー -->
          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <div
              v-if="isActionMenuOpen"
              class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200"
            >
              <div class="py-1">
                <NuxtLink
                  :to="`/shops/${shop.id}`"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  @click="closeActionMenu"
                >
                  詳細を見る
                </NuxtLink>
                <button
                  @click="$emit('edit', shop)"
                  class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  編集
                </button>
                <button
                  @click="$emit('delete', shop)"
                  class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100"
                >
                  削除
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <!-- カテゴリタグ -->
      <div class="mt-3 flex flex-wrap gap-2">
        <span
          v-for="category in shop.categories"
          :key="category.id"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
        >
          {{ category.name }}
        </span>
      </div>

      <!-- 評価と統計 -->
      <div class="mt-4 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <div v-if="shop.average_rating" class="flex items-center">
            <svg
              class="w-4 h-4 text-yellow-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
              ></path>
            </svg>
            <span class="text-sm font-medium text-gray-900 ml-1">
              {{ shop.average_rating.toFixed(1) }}
            </span>
          </div>
          <span class="text-sm text-gray-500">
            ({{ shop.reviews_count || 0 }}件)
          </span>
        </div>

        <span class="text-xs text-gray-400">
          {{ formatDate(shop.updated_at) }}
        </span>
      </div>

      <!-- アクションボタン（認証済みユーザーのみ） -->
      <div
        v-if="showQuickActions && authStore.isLoggedIn"
        class="mt-4 flex space-x-2"
      >
        <button
          @click.stop="$emit('addReview', shop)"
          class="flex-1 bg-blue-50 text-blue-700 text-sm font-medium py-3 px-3 rounded-md hover:bg-blue-100 transition-colors touch-manipulation"
        >
          レビュー追加
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  shop: any
  showActions?: boolean
  showQuickActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showActions: true,
  showQuickActions: false,
})

const authStore = useAuthStore()

// const emit = defineEmits<{
//   edit: [shop: any]
//   delete: [shop: any]
//   addReview: [shop: any]
// }>()

// アクションメニュー制御
const isActionMenuOpen = ref(false)

// 画像読み込み状態
const imageLoading = ref(true)
const imageError = ref(false)
const shouldLoadImage = ref(false)
const imageContainer = ref<HTMLElement>()

// タッチ操作
const touchStartTime = ref(0)
const touchMoved = ref(false)

const toggleActionMenu = () => {
  isActionMenuOpen.value = !isActionMenuOpen.value
}

const closeActionMenu = () => {
  isActionMenuOpen.value = false
}

// ユーティリティ関数
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}

// カードクリック時の店舗詳細遷移
const navigateToShop = () => {
  navigateTo(`/shops/${props.shop.id}`)
}

// 画像読み込み処理
const handleImageLoad = () => {
  imageLoading.value = false
  imageError.value = false
}

const handleImageError = () => {
  imageLoading.value = false
  imageError.value = true
}

// タッチ操作ハンドラー
const handleTouchStart = (event: TouchEvent) => {
  touchStartTime.value = Date.now()
  touchMoved.value = false

  // タッチスタート時に軽い視覚的フィードバック
  const target = event.currentTarget as HTMLElement
  target.style.transform = 'scale(0.98)'
  target.style.transition = 'transform 0.1s ease-out'
}

const handleTouchEnd = (event: TouchEvent) => {
  const target = event.currentTarget as HTMLElement
  target.style.transform = 'scale(1)'
  target.style.transition = 'transform 0.2s ease-out'

  // 長押しやスワイプの場合は navigation をキャンセル
  const touchDuration = Date.now() - touchStartTime.value
  if (touchDuration > 500 || touchMoved.value) {
    event.preventDefault()
    return
  }
}

// 遅延読み込み（Intersection Observer）
onMounted(() => {
  if (imageContainer.value) {
    const observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            shouldLoadImage.value = true
            observer.unobserve(entry.target)
          }
        })
      },
      {
        rootMargin: '50px',
      }
    )

    observer.observe(imageContainer.value)

    onUnmounted(() => {
      observer.disconnect()
    })
  }
})

// 外部クリックでメニューを閉じる（今後実装予定）
// const handleClickOutside = (event: Event) => {
//   // このロジックは親コンポーネントで処理する方が効率的
// }
</script>
