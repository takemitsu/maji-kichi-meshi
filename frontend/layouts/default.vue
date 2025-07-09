<template>
  <div class="min-h-screen flex flex-col">
    <!-- ヘッダー -->
    <TheHeader />

    <!-- メインコンテンツ -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- フッター -->
    <TheFooter />
  </div>
</template>

<script setup lang="ts">
// レイアウトでの初期化処理があればここに記述
</script>
