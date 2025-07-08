<template>
  <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="mb-8">
          <h1 class="text-2xl font-bold text-gray-900">
            ダッシュボード
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            おかえりなさい、{{ authStore.user?.name }}さん
          </p>
        </div>

        <!-- 統計情報 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m-2 0h2m0 0h4"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    登録店舗数
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.shopsCount }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    レビュー数
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.reviewsCount }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    ランキング
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.rankingsCount }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6H3a1 1 0 110-2h4zM9 6h6v10H9V6z"></path>
                  </svg>
                </div>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    カテゴリ数
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.categoriesCount }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- アクションボタン -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">店舗管理</h3>
            <p class="text-sm text-gray-600 mb-4">
              お気に入りの店舗を登録・編集します
            </p>
            <NuxtLink
              to="/shops"
              class="btn-primary inline-block text-center w-full"
            >
              店舗を管理
            </NuxtLink>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">レビュー</h3>
            <p class="text-sm text-gray-600 mb-4">
              訪問記録とレビューを作成・編集します
            </p>
            <NuxtLink
              to="/reviews"
              class="btn-primary inline-block text-center w-full"
            >
              レビューを管理
            </NuxtLink>
          </div>

          <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">ランキング</h3>
            <p class="text-sm text-gray-600 mb-4">
              個人的なランキングを作成・編集します
            </p>
            <NuxtLink
              to="/rankings"
              class="btn-primary inline-block text-center w-full"
            >
              ランキングを編集
            </NuxtLink>
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

const authStore = useAuthStore()

// 統計情報
const stats = ref({
  shopsCount: 0,
  reviewsCount: 0,
  rankingsCount: 0,
  categoriesCount: 0
})

// データ取得
const { $api } = useNuxtApp()

onMounted(async () => {
  try {
    // 各種統計データを取得
    // 実際のAPIが実装されるまでは仮のデータ
    stats.value = {
      shopsCount: 15,
      reviewsCount: 23,
      rankingsCount: 5,
      categoriesCount: 8
    }
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  }
})

// ログアウト処理はヘッダーコンポーネントで処理

// メタデータ設定
useHead({
  title: 'ダッシュボード - マジキチメシ',
  meta: [
    { name: 'description', content: 'マジキチメシのダッシュボードページ' }
  ]
})
</script>