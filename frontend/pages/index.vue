<template>
  <div class="container mx-auto px-4 py-8">
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">マジキチメシ</h1>
      <p class="text-xl text-gray-600">吉祥寺地域の個人的な店舗ランキング</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div class="card">
        <h3 class="text-xl font-semibold mb-4">店舗検索</h3>
        <p class="text-gray-600 mb-4">お気に入りの店舗を検索・閲覧できます</p>
        <NuxtLink to="/shops" class="btn-primary inline-block text-center">店舗を検索</NuxtLink>
        <p class="text-xs text-gray-500 mt-2">※ 閲覧はログイン不要</p>
      </div>

      <div class="card">
        <h3 class="text-xl font-semibold mb-4">レビュー</h3>
        <p class="text-gray-600 mb-4">他の人の訪問記録を閲覧できます</p>
        <NuxtLink to="/reviews" class="btn-primary inline-block text-center"
          >レビューを見る</NuxtLink
        >
        <p class="text-xs text-gray-500 mt-2">※ 閲覧はログイン不要</p>
      </div>

      <div class="card">
        <h3 class="text-xl font-semibold mb-4">ランキング</h3>
        <p class="text-gray-600 mb-4">公開されているランキングを見ることができます</p>
        <NuxtLink to="/rankings/public" class="btn-primary inline-block text-center"
          >ランキングを見る</NuxtLink
        >
        <p class="text-xs text-gray-500 mt-2">※ 閲覧はログイン不要</p>
      </div>
    </div>

    <div class="mt-12 text-center">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">ログインして始めましょう</h2>
        <p class="text-gray-600 mb-6">Google、GitHub、LINE、Twitterでログイン可能</p>
        <div class="space-x-4">
          <NuxtLink to="/login" class="btn-primary">ログイン</NuxtLink>
          <NuxtLink to="/rankings/public" class="btn-secondary inline-block">ゲストで見る</NuxtLink>
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

// ボタンのクリックハンドラー
const handleFeatureClick = (route: string) => {
  if (authStore.isLoggedIn) {
    navigateTo(route)
  } else {
    navigateTo('/login')
  }
}

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
