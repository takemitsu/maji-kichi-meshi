<template>
  <div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div class="card">
        <h3 class="text-xl font-semibold mb-4">店舗検索</h3>
        <NuxtLink to="/shops" class="btn-primary inline-block text-center">店舗を検索</NuxtLink>
        <p class="text-xs text-gray-500 mt-2">※ 閲覧はログイン不要</p>
      </div>

      <div class="card">
        <h3 class="text-xl font-semibold mb-4">レビュー</h3>
        <NuxtLink to="/reviews" class="btn-primary inline-block text-center"
          >レビューを見る</NuxtLink
        >
        <p class="text-xs text-gray-500 mt-2">※ 閲覧はログイン不要</p>
      </div>

      <div class="card">
        <h3 class="text-xl font-semibold mb-4">ランキング</h3>
        <NuxtLink to="/rankings/public" class="btn-primary inline-block text-center"
          >ランキングを見る</NuxtLink
        >
        <p class="text-xs text-gray-500 mt-2">※ 閲覧はログイン不要</p>
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
