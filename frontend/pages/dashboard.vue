<template>
    <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">マイページ</h1>
                <p class="mt-2 text-sm text-gray-600">おかえりなさい、{{ authStore.user?.name }}さん</p>
            </div>

            <!-- 統計情報 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-8">
                <NuxtLink
                    :to="`/reviews?user_id=${authStore.user?.id}`"
                    class="bg-white rounded-lg shadow p-4 md:p-6 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-700">レビュー</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ stats.reviewsCount }}
                                </dd>
                            </dl>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </NuxtLink>

                <NuxtLink
                    to="/rankings"
                    class="bg-white rounded-lg shadow p-4 md:p-6 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-700">ランキング</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ stats.rankingsCount }}
                                </dd>
                            </dl>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </NuxtLink>

                <NuxtLink
                    to="/my/liked-reviews"
                    class="bg-white rounded-lg shadow p-4 md:p-6 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg
                                    class="w-5 h-5 text-white"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path
                                        d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-700">いいねしたレビュー</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ stats.likedReviewsCount }}
                                </dd>
                            </dl>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </NuxtLink>

                <NuxtLink
                    to="/my/wishlists"
                    class="bg-white rounded-lg shadow p-4 md:p-6 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                                <svg
                                    class="w-5 h-5 text-white"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    fill="none"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-700">行きたいリスト</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    {{ stats.wishlistsCount }}
                                </dd>
                            </dl>
                        </div>
                        <div class="flex-shrink-0">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </NuxtLink>
            </div>

            <!-- アクションボタン -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">ランキング</h3>
                    <NuxtLink to="/rankings/public" class="btn-primary inline-block text-center w-full">
                        ランキングを見る
                    </NuxtLink>
                </div>

                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">レビュー</h3>
                    <NuxtLink to="/reviews" class="btn-primary inline-block text-center w-full">レビューを見る</NuxtLink>
                </div>

                <div class="bg-white rounded-lg shadow p-4 md:p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">店舗</h3>
                    <NuxtLink to="/shops" class="btn-primary inline-block text-center w-full">店舗を見る</NuxtLink>
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

const authStore = useAuthStore()

// 統計情報
const stats = ref({
    reviewsCount: 0,
    rankingsCount: 0,
    likedReviewsCount: 0,
    wishlistsCount: 0,
})

// データ取得
// const { $api } = useNuxtApp() // 将来のAPI実装時に使用

onMounted(async () => {
    try {
        // 統計データをAPIから取得
        const { $api } = useNuxtApp()
        const response = await $api.stats.dashboard()

        stats.value = {
            reviewsCount: response.data.reviews_count,
            rankingsCount: response.data.rankings_count,
            likedReviewsCount: response.data.liked_reviews_count,
            wishlistsCount: response.data.wishlists_count,
        }
    } catch (error) {
        console.error('Failed to load dashboard data:', error)
        // エラー時は0にフォールバック
        stats.value = {
            reviewsCount: 0,
            rankingsCount: 0,
            likedReviewsCount: 0,
            wishlistsCount: 0,
        }
    }
})

// ログアウト処理はヘッダーコンポーネントで処理

// メタデータ設定
useHead({
    title: 'マイページ - マジキチメシ',
    meta: [{ name: 'description', content: 'マジキチメシのマイページ' }],
})
</script>
