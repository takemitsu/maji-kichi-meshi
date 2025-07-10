<template>
  <div class="mx-auto max-w-6xl py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
      <!-- ローディング -->
      <LoadingSpinner v-if="loading" fullscreen />

      <!-- エラーメッセージ -->
      <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

      <!-- ランキング詳細 -->
      <div v-if="ranking && !loading">
        <!-- ブレッドクラム -->
        <nav class="flex mb-6" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-4">
            <li>
              <NuxtLink
                :to="ranking.is_public ? '/rankings/public' : '/rankings'"
                class="text-gray-500 hover:text-gray-700"
              >
                {{ ranking.is_public ? '公開ランキング' : 'マイランキング' }}
              </NuxtLink>
            </li>
            <li>
              <svg
                class="flex-shrink-0 h-5 w-5 text-gray-400 fill-current"
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
              {{ ranking.title }}
            </li>
          </ol>
        </nav>

        <!-- ヘッダー -->
        <div class="mb-8">
          <div class="md:flex md:items-start md:justify-between">
            <div class="min-w-0 flex-1">
              <div class="flex items-center space-x-3">
                <h1
                  class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight"
                >
                  {{ ranking.title }}
                </h1>

                <!-- ステータスバッジ -->
                <span
                  class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                  :class="{
                    'bg-green-100 text-green-800': ranking.is_public,
                    'bg-gray-100 text-gray-800': !ranking.is_public,
                  }"
                >
                  {{ ranking.is_public ? '公開' : '非公開' }}
                </span>
              </div>

              <div class="mt-2 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <div
                    class="w-5 h-5 bg-gray-300 rounded-full flex items-center justify-center mr-2"
                  >
                    <span class="text-xs font-medium text-gray-700">
                      {{ ranking.user?.name?.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  {{ ranking.user?.name }}
                </div>

                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <svg
                    class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400 fill-none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                    ></path>
                  </svg>
                  {{ ranking.category?.name || '総合' }}
                </div>

                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <svg
                    class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400 fill-none"
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
                  {{ ranking.shop ? 1 : 0 }}店舗
                </div>

                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <svg
                    class="flex-shrink-0 mr-1.5 h-5 w-5 text-gray-400 fill-none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
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

              <p v-if="ranking.description" class="mt-4 text-gray-700">
                {{ ranking.description }}
              </p>
            </div>

            <!-- アクション（自分のランキングの場合のみ） -->
            <div v-if="isOwner" class="mt-4 flex space-x-3 md:ml-4 md:mt-0">
              <NuxtLink :to="`/rankings/${ranking.id}/edit`" class="btn-secondary flex items-center">
                <svg class="w-4 h-4 mr-2 fill-none" stroke="currentColor" viewBox="0 0 24 24">
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
                @click="deleteRanking"
                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors flex items-center"
              >
                <svg class="w-4 h-4 mr-2 fill-none" stroke="currentColor" viewBox="0 0 24 24">
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

        <!-- ランキング本体 -->
        <div class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">{{ ranking.title }} ランキング</h3>
          </div>

          <!-- 店舗ランキング -->
          <div v-if="ranking.shop" class="divide-y divide-gray-200">
            <div
              class="p-6 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center space-x-4">
                <!-- 順位 -->
                <div
                  class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold bg-yellow-100 text-yellow-800 ring-2 ring-yellow-400"
                >
                  🥇
                </div>

                <!-- 店舗情報 -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h4 class="text-lg font-semibold text-gray-900">
                        <NuxtLink
                          :to="`/shops/${ranking.shop.id}`"
                          class="hover:text-blue-600 transition-colors"
                        >
                          {{ ranking.shop.name }}
                        </NuxtLink>
                      </h4>
                      <p class="text-sm text-gray-600 mt-1">
                        {{ ranking.shop.address }}
                      </p>

                      <!-- カテゴリタグ -->
                      <div class="mt-2 flex flex-wrap gap-2">
                        <span
                          v-for="category in ranking.shop.categories"
                          :key="category.id"
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                        >
                          {{ category.name }}
                        </span>
                      </div>
                    </div>

                    <!-- 統計情報 -->
                    <div class="ml-4 text-right">
                      <div v-if="ranking.shop.average_rating" class="flex items-center justify-end mb-1">
                        <svg
                          class="w-4 h-4 text-yellow-400 mr-1 fill-current"
                          viewBox="0 0 20 20"
                        >
                          <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"
                          ></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-900">
                          {{ ranking.shop.average_rating.toFixed(1) }}
                        </span>
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ ranking.shop.review_count || 0 }}件のレビュー
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 空の状態 -->
          <div v-else class="px-6 py-12 text-center">
            <svg
              class="mx-auto h-12 w-12 text-gray-400 fill-none"
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
            <h3 class="mt-2 text-sm font-medium text-gray-900">店舗が登録されていません</h3>
            <p class="mt-1 text-sm text-gray-500">まだランキングに店舗が追加されていません。</p>
            <div v-if="isOwner" class="mt-6">
              <NuxtLink :to="`/rankings/${ranking.id}/edit`" class="btn-primary">
                店舗を追加する
              </NuxtLink>
            </div>
          </div>
        </div>

        <!-- メタデータ -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">詳細情報</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
              <label class="block font-medium text-gray-700">ランキングID</label>
              <p class="text-gray-900">{{ ranking.id }}</p>
            </div>
            <div>
              <label class="block font-medium text-gray-700">作成日</label>
              <p class="text-gray-900">
                {{ formatDateTime(ranking.created_at) }}
              </p>
            </div>
            <div v-if="ranking.updated_at !== ranking.created_at">
              <label class="block font-medium text-gray-700">最終更新</label>
              <p class="text-gray-900">
                {{ formatDateTime(ranking.updated_at) }}
              </p>
            </div>
            <div>
              <label class="block font-medium text-gray-700">公開状態</label>
              <p class="text-gray-900">
                {{ ranking.is_public ? '公開' : '非公開' }}
              </p>
            </div>
          </div>
        </div>

        <!-- 関連アクション -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">関連アクション</h3>
          <div class="flex flex-wrap gap-4">
            <NuxtLink
              :to="ranking.is_public ? '/rankings/public' : '/rankings'"
              class="btn-secondary"
            >
              {{ ranking.is_public ? '公開ランキング' : 'マイランキング' }}一覧に戻る
            </NuxtLink>

            <NuxtLink
              v-if="ranking.category"
              :to="`/rankings/public?category_id=${ranking.category.id}`"
              class="btn-secondary"
            >
              {{ ranking.category.name }}の他のランキング
            </NuxtLink>

            <NuxtLink v-if="!isOwner" to="/rankings/create" class="btn-primary">
              自分もランキングを作成
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Ranking } from '~/types/api'

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()
const authStore = useAuthStore()

// リアクティブデータ
const ranking = ref<Ranking | null>(null)
const loading = ref(true)
const error = ref('')

const rankingId = computed(() => parseInt(route.params.id as string))

// 所有者チェック
const isOwner = computed(() => {
  return ranking.value?.user?.id === authStore.user?.id
})

// ランキングデータ取得
const loadRanking = async () => {
  try {
    loading.value = true
    const response = await $api.rankings.get(rankingId.value)
    ranking.value = response.data
  } catch (err: unknown) {
    console.error('Failed to load ranking:', err)
    if (err && typeof err === 'object' && 'status' in err) {
      const errorObj = err as { status: number }
      if (errorObj.status === 404) {
        error.value = 'ランキングが見つかりませんでした'
      } else if (errorObj.status === 403) {
        error.value = 'このランキングを閲覧する権限がありません'
      } else {
        error.value = 'ランキングデータの取得に失敗しました'
      }
    } else {
      error.value = 'ランキングデータの取得に失敗しました'
    }
  } finally {
    loading.value = false
  }
}

// ランキング削除
const deleteRanking = async () => {
  if (!ranking.value || !confirm(`「${ranking.value.title}」を削除しますか？この操作は元に戻せません。`)) {
    return
  }

  try {
    await $api.rankings.delete(rankingId.value)
    await router.push('/rankings')
  } catch (err: unknown) {
    console.error('Failed to delete ranking:', err)
    if (err && typeof err === 'object' && 'status' in err) {
      const errorObj = err as { status: number }
      if (errorObj.status === 403) {
        error.value = 'このランキングを削除する権限がありません'
      } else {
        error.value = 'ランキングの削除に失敗しました'
      }
    } else {
      error.value = 'ランキングの削除に失敗しました'
    }
  }
}

// ユーティリティ関数
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('ja-JP')
}

const formatDateTime = (dateString: string) => {
  return new Date(dateString).toLocaleString('ja-JP')
}

// 初期化
onMounted(async () => {
  await loadRanking()
})

// メタデータ設定
useHead(() => ({
  title: ranking.value
    ? `${ranking.value.title} - ランキング詳細 - マジキチメシ`
    : 'ランキング詳細 - マジキチメシ',
  meta: [
    {
      name: 'description',
      content: ranking.value ? `${ranking.value.title}の詳細ランキング` : 'ランキング詳細ページ',
    },
  ],
}))
</script>
