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
              公開ランキング
            </h1>
            <p class="mt-1 text-sm text-gray-500">
              みんなが公開している吉祥寺の店舗ランキングを見ることができます
            </p>
          </div>
          <div class="mt-4 flex md:ml-4 md:mt-0">
            <NuxtLink to="/rankings" class="btn-secondary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                ></path>
              </svg>
              マイランキング
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- フィルター -->
      <div class="mb-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- 検索 -->
          <div>
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
                placeholder="ランキング名やユーザー名で検索..."
                class="w-full py-2 pr-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <!-- カテゴリフィルター -->
          <div>
            <select v-model="selectedCategory" @change="handleFilter" class="input-field">
              <option value="">全てのカテゴリ</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- ローディング -->
      <LoadingSpinner v-if="loading" />

      <!-- エラーメッセージ -->
      <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

      <!-- 公開ランキング一覧 -->
      <div v-if="!loading && rankings.length > 0" class="space-y-6">
        <div
          v-for="ranking in rankings"
          :key="ranking.id"
          class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-6">
            <!-- ヘッダー部分 -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-3">
                  <h3 class="text-lg font-semibold text-gray-900">
                    <NuxtLink
                      :to="`/rankings/${ranking.id}`"
                      class="hover:text-blue-600 transition-colors"
                    >
                      {{ ranking.title }}
                    </NuxtLink>
                  </h3>

                  <!-- 公開バッジ -->
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                  >
                    公開
                  </span>
                </div>

                <p v-if="ranking.description" class="text-sm text-gray-600 mt-2">
                  {{ ranking.description }}
                </p>

                <div class="flex items-center space-x-4 mt-3">
                  <!-- 作成者 -->
                  <div class="flex items-center text-sm text-gray-500">
                    <div
                      class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center mr-2"
                    >
                      <span class="text-xs font-medium text-gray-700">
                        {{ ranking.user?.name?.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    {{ ranking.user?.name }}
                  </div>

                  <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                      ></path>
                    </svg>
                    {{ ranking.category?.name || '総合' }}
                  </div>

                  <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m-2 0h2m0 0h4"
                      ></path>
                    </svg>
                    {{ ranking.shops_count || 0 }}店舗
                  </div>

                  <div class="flex items-center text-sm text-gray-500">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                      ></path>
                    </svg>
                    {{ formatDate(ranking.updated_at) }}
                  </div>
                </div>
              </div>

              <!-- アクション -->
              <div class="flex items-center space-x-2">
                <NuxtLink :to="`/rankings/${ranking.id}`" class="btn-primary text-sm">
                  詳細を見る
                </NuxtLink>
              </div>
            </div>

            <!-- 上位店舗プレビュー -->
            <div
              v-if="ranking.shops && ranking.shops.length > 0"
              class="border-t border-gray-200 pt-4"
            >
              <h4 class="text-sm font-medium text-gray-700 mb-3">上位店舗</h4>
              <div class="space-y-2">
                <div
                  v-for="(shop, index) in ranking.shops.slice(0, 5)"
                  :key="shop.id"
                  class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg"
                >
                  <!-- 順位 -->
                  <div
                    class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold"
                    :class="{
                      'bg-yellow-100 text-yellow-800': index === 0,
                      'bg-gray-100 text-gray-800': index === 1,
                      'bg-orange-100 text-orange-800': index === 2,
                      'bg-blue-100 text-blue-800': index >= 3,
                    }"
                  >
                    {{ index + 1 }}
                  </div>

                  <!-- 店舗情報 -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <div>
                        <p class="text-sm font-medium text-gray-900">
                          {{ shop.name }}
                        </p>
                        <p class="text-xs text-gray-500">
                          {{ shop.address }}
                        </p>
                      </div>

                      <!-- 評価情報（もしあれば） -->
                      <div
                        v-if="shop.average_rating"
                        class="flex items-center text-xs text-gray-500"
                      >
                        <svg
                          class="w-3 h-3 text-yellow-400 mr-1"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                          ></path>
                        </svg>
                        {{ shop.average_rating.toFixed(1) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="ranking.shops.length > 5" class="mt-3 text-center">
                <NuxtLink
                  :to="`/rankings/${ranking.id}`"
                  class="text-sm text-blue-600 hover:text-blue-800"
                >
                  残り{{ ranking.shops.length - 5 }}店舗を見る
                </NuxtLink>
              </div>
            </div>

            <!-- 空の状態 -->
            <div v-else class="border-t border-gray-200 pt-4 text-center">
              <p class="text-sm text-gray-500">店舗が登録されていません</p>
            </div>
          </div>
        </div>

        <!-- ページネーション -->
        <div v-if="totalPages > 1" class="flex justify-center mt-8">
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
      <div v-if="!loading && rankings.length === 0" class="text-center py-12">
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
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
          ></path>
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
          ></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">公開ランキングがありません</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{
            searchQuery || selectedCategory
              ? '検索条件に一致する公開ランキングが見つかりませんでした。'
              : 'まだ公開されているランキングがありません。'
          }}
        </p>
        <div class="mt-6">
          <NuxtLink to="/rankings/create" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              ></path>
            </svg>
            あなたもランキングを作成
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Ranking, Category } from '~/types/api'

const { $api } = useNuxtApp()

// リアクティブデータ
const rankings = ref<Ranking[]>([])
const categories = ref<Category[]>([])
const loading = ref(true)
const error = ref('')
const searchQuery = ref('')
const selectedCategory = ref('')

// ページネーション
const currentPage = ref(1)
const perPage = ref(20)
const totalItems = ref(0)
const totalPages = ref(0)

// 検索とフィルター
const handleSearch = useDebounceFn(() => {
  currentPage.value = 1 // 検索時は1ページ目に戻る
  loadRankings()
}, 300)

const handleFilter = () => {
  currentPage.value = 1 // フィルター変更時は1ページ目に戻る
  loadRankings()
}

// ページ変更
const handlePageChange = (page: number) => {
  currentPage.value = page
  loadRankings()
}

// 公開ランキングデータ取得
const loadRankings = async () => {
  try {
    loading.value = true

    const params: Record<string, string | number | boolean> = {
      page: currentPage.value,
      per_page: perPage.value,
    }
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedCategory.value) params.category_id = selectedCategory.value

    const response = await $api.rankings.publicRankings(params)
    
    // ページネーション対応のレスポンス処理
    rankings.value = response.data || []
    
    if (response.meta) {
      currentPage.value = response.meta.current_page
      perPage.value = response.meta.per_page
      totalItems.value = response.meta.total
      totalPages.value = response.meta.last_page
    }
  } catch (err) {
    console.error('Failed to load public rankings:', err)
    error.value = '公開ランキングデータの取得に失敗しました'
  } finally {
    loading.value = false
  }
}

// カテゴリデータ取得
const loadCategories = async () => {
  try {
    const response = await $api.categories.list()
    categories.value = response.data || []
  } catch (err) {
    console.error('Failed to load categories:', err)
  }
}

// ユーティリティ関数
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}

// 初期化
onMounted(async () => {
  await Promise.all([loadRankings(), loadCategories()])
})

// メタデータ設定
useHead({
  title: '公開ランキング - マジキチメシ',
  meta: [
    {
      name: 'description',
      content: 'みんなが公開している吉祥寺の店舗ランキング一覧',
    },
  ],
})
</script>
