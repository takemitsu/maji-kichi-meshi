<template>
  <div class="container mx-auto px-4 py-8">
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">マジキチメシ</h1>
      <p class="text-xl text-gray-600">吉祥寺地域の個人的な店舗ランキング</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <div class="card">
        <h3 class="text-xl font-semibold mb-4">店舗検索</h3>
        <p class="text-gray-600 mb-4">お気に入りの店舗を検索して登録しましょう</p>
        <button class="btn-primary">店舗を検索</button>
      </div>
      
      <div class="card">
        <h3 class="text-xl font-semibold mb-4">レビュー</h3>
        <p class="text-gray-600 mb-4">訪問した店舗の評価とメモを記録</p>
        <button class="btn-primary">レビューを見る</button>
      </div>
      
      <div class="card">
        <h3 class="text-xl font-semibold mb-4">マイランキング</h3>
        <p class="text-gray-600 mb-4">個人的なランキングを作成・編集</p>
        <button class="btn-primary">ランキングを見る</button>
      </div>
    </div>
    
    <div class="mt-12 text-center">
      <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">ログインして始めましょう</h2>
        <p class="text-gray-600 mb-6">Google、GitHub、LINE、Twitterでログイン可能</p>
        <div class="space-x-4">
          <NuxtLink to="/login" class="btn-primary">ログイン</NuxtLink>
          <button class="btn-secondary">ゲストで見る</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// メタデータ設定
useHead({
  title: 'マジキチメシ - 吉祥寺地域の個人的な店舗ランキング',
  meta: [
    { name: 'description', content: '吉祥寺地域の個人的な店舗ランキング作成・共有アプリ' }
  ]
})
</script>