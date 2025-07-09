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
              ランキング管理
            </h1>
            <p class="mt-1 text-sm text-gray-500">
              あなたの個人的な店舗ランキングを作成・管理できます
            </p>
          </div>
          <div class="mt-4 flex space-x-3 md:ml-4 md:mt-0">
            <NuxtLink to="/rankings/public" class="btn-secondary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
              公開ランキング
            </NuxtLink>
            <NuxtLink to="/rankings/create" class="btn-primary">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                ></path>
              </svg>
              ランキングを作成
            </NuxtLink>
          </div>
        </div>
      </div>

      <!-- フィルター -->
      <div class="mb-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                placeholder="ランキング名で検索..."
                class="input-field pl-10"
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

          <!-- 公開状態フィルター -->
          <div>
            <select v-model="selectedVisibility" @change="handleFilter" class="input-field">
              <option value="">全ての状態</option>
              <option value="public">公開</option>
              <option value="private">非公開</option>
            </select>
          </div>
        </div>
      </div>

      <!-- ローディング -->
      <LoadingSpinner v-if="loading" />

      <!-- エラーメッセージ -->
      <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

      <!-- ランキング一覧 -->
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

                  <!-- 公開状態バッジ -->
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="{
                      'bg-green-100 text-green-800': ranking.is_public,
                      'bg-gray-100 text-gray-800': !ranking.is_public,
                    }"
                  >
                    {{ ranking.is_public ? '公開' : '非公開' }}
                  </span>
                </div>

                <p v-if="ranking.description" class="text-sm text-gray-600 mt-2">
                  {{ ranking.description }}
                </p>

                <div class="flex items-center space-x-4 mt-3">
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
                    更新: {{ formatDate(ranking.updated_at) }}
                  </div>
                </div>
              </div>

              <!-- アクションメニュー -->
              <div class="flex items-center space-x-2">
                <NuxtLink
                  :to="`/rankings/${ranking.id}`"
                  class="text-sm text-blue-600 hover:text-blue-800"
                >
                  詳細
                </NuxtLink>
                <NuxtLink
                  :to="`/rankings/${ranking.id}/edit`"
                  class="text-sm text-gray-600 hover:text-gray-800"
                >
                  編集
                </NuxtLink>
                <button
                  @click="deleteRanking(ranking)"
                  class="text-sm text-red-600 hover:text-red-800"
                >
                  削除
                </button>
              </div>
            </div>

            <!-- 上位店舗プレビュー -->
            <div
              v-if="ranking.shops && ranking.shops.length > 0"
              class="border-t border-gray-200 pt-4"
            >
              <h4 class="text-sm font-medium text-gray-700 mb-3">上位店舗</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div
                  v-for="(shop, index) in ranking.shops.slice(0, 3)"
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
                    }"
                  >
                    {{ index + 1 }}
                  </div>

                  <!-- 店舗情報 -->
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ shop.name }}
                    </p>
                    <p class="text-xs text-gray-500 truncate">
                      {{ shop.address }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-if="ranking.shops.length > 3" class="mt-3 text-center">
                <NuxtLink
                  :to="`/rankings/${ranking.id}`"
                  class="text-sm text-blue-600 hover:text-blue-800"
                >
                  残り{{ ranking.shops.length - 3 }}店舗を見る
                </NuxtLink>
              </div>
            </div>

            <!-- 空の状態 -->
            <div v-else class="border-t border-gray-200 pt-4 text-center">
              <p class="text-sm text-gray-500">まだ店舗が登録されていません</p>
              <NuxtLink
                :to="`/rankings/${ranking.id}/edit`"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                店舗を追加する
              </NuxtLink>
            </div>
          </div>
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
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
          ></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">ランキングがありません</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{
            searchQuery || selectedCategory || selectedVisibility
              ? '検索条件に一致するランキングが見つかりませんでした。'
              : '最初のランキングを作成してみましょう。'
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
            ランキングを作成
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 認証ミドルウェア適用
definePageMeta({
  middleware: 'auth',
})

const { $api } = useNuxtApp()

// リアクティブデータ
const rankings = ref<any[]>([])
const categories = ref<any[]>([])
const loading = ref(true)
const error = ref('')
const searchQuery = ref('')
const selectedCategory = ref('')
const selectedVisibility = ref('')

// 検索とフィルター
const handleSearch = useDebounceFn(() => {
  loadRankings()
}, 300)

const handleFilter = () => {
  loadRankings()
}

// ランキングデータ取得
const loadRankings = async () => {
  try {
    loading.value = true

    const params: Record<string, any> = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedCategory.value) params.category_id = selectedCategory.value
    if (selectedVisibility.value) params.is_public = selectedVisibility.value === 'public'

    const response = await $api.rankings.myRankings(params)
    rankings.value = response.data || []
  } catch (err) {
    console.error('Failed to load rankings:', err)
    error.value = 'ランキングデータの取得に失敗しました'
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

// ランキング削除
const deleteRanking = async (ranking: any) => {
  if (!confirm(`「${ranking.title}」を削除しますか？この操作は元に戻せません。`)) {
    return
  }

  try {
    await $api.rankings.delete(ranking.id)
    await loadRankings()
  } catch (err) {
    console.error('Failed to delete ranking:', err)
    error.value = 'ランキングの削除に失敗しました'
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
  title: 'ランキング管理 - マジキチメシ',
  meta: [{ name: 'description', content: '個人的な店舗ランキングの管理ページ' }],
})
</script>
