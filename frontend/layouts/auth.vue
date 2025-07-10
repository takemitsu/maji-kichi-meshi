<template>
  <div class="min-h-screen bg-gray-50">
    <!-- 認証ページ用のシンプルなヘッダー -->
    <header class="bg-white border-b border-gray-200">
      <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- ロゴ -->
          <div class="flex items-center">
            <NuxtLink
              to="/"
              class="text-xl font-bold text-gray-900 hover:text-blue-600 transition-colors"
            >
              マジキチメシ
            </NuxtLink>
          </div>
          
          <!-- 戻るボタン -->
          <div>
            <NuxtLink
              to="/"
              class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
            >
              <svg class="w-4 h-4 mr-2 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              戻る
            </NuxtLink>
          </div>
        </div>
      </nav>
    </header>

    <!-- メインコンテンツ -->
    <slot />
  </div>
</template>

<script setup lang="ts">
// 認証ページ専用レイアウト
// ヘッダー・フッターなしのシンプルな構成
</script>
