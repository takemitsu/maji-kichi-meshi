<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- ヒーローセクション -->
        <div class="container mx-auto px-4 pt-16 pb-8">
            <div class="text-center mb-16">
                <h1 class="text-5xl font-bold text-gray-900 mb-4">マジキチメシ</h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    吉祥寺で見つけた「マジで美味い」店舗を
                    <br />
                    あなただけのランキングで管理しよう
                </p>
            </div>

            <!-- 機能カード -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div
                                class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-purple-200 transition-colors">
                                <svg class="w-5 h-5 text-purple-600 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">ランキング</h3>
                        </div>
                        <div class="text-center">
                            <NuxtLink
                                to="/rankings/public"
                                class="btn-primary inline-block text-center transform group-hover:scale-105 transition-transform">
                                ランキングを見る
                            </NuxtLink>
                            <p class="text-xs text-gray-700 mt-4">※ ログイン不要</p>
                        </div>
                    </div>
                </div>

                <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div
                                class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-green-200 transition-colors">
                                <svg class="w-5 h-5 text-green-600 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">レビュー</h3>
                        </div>
                        <div class="text-center">
                            <NuxtLink
                                to="/reviews"
                                class="btn-primary inline-block text-center transform group-hover:scale-105 transition-transform">
                                レビューを見る
                            </NuxtLink>
                            <p class="text-xs text-gray-700 mt-4">※ ログイン不要</p>
                        </div>
                    </div>
                </div>

                <div class="group bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                    <div class="p-8">
                        <div class="flex items-center mb-6">
                            <div
                                class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-orange-200 transition-colors">
                                <svg class="w-5 h-5 text-orange-600 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900">店舗検索</h3>
                        </div>
                        <div class="text-center">
                            <NuxtLink
                                to="/shops"
                                class="btn-primary inline-block text-center transform group-hover:scale-105 transition-transform">
                                店舗を探す
                            </NuxtLink>
                            <p class="text-xs text-gray-700 mt-4">※ ログイン不要</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA セクション -->
            <div class="text-center mt-16">
                <div class="inline-block bg-white rounded-2xl shadow-lg p-8 max-w-md mx-auto">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-blue-600 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">自分だけのランキングを作成</h3>
                    <p class="text-gray-600 text-sm mb-6">ログインして店舗評価とランキング作成を始めましょう</p>
                    <NuxtLink to="/login" class="btn-primary inline-block">ログインして始める</NuxtLink>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const authStore = useAuthStore()

// 認証済みユーザーは自動的にダッシュボードにリダイレクト
if (authStore.isLoggedIn) {
    await navigateTo('/dashboard')
}

// ボタンのクリックハンドラー（将来のボタン実装時に使用）
// const handleFeatureClick = (route: string) => {
//   if (authStore.isLoggedIn) {
//     navigateTo(route)
//   } else {
//     navigateTo('/login')
//   }
// }

// メタデータ設定
useHead({
    title: 'マジキチメシ - 吉祥寺地域の個人的な店舗ランキング',
    meta: [
        {
            name: 'description',
            content: '吉祥寺地域の個人的な店舗ランキング作成・共有アプリ',
        },
    ],
})
</script>
