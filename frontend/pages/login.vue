<template>
  <div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" style="min-height: calc(100vh - 64px)">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          マジキチメシにログイン
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          吉祥寺地域の個人的な店舗ランキングを作成・共有
        </p>
      </div>

      <!-- エラーメッセージ -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400 fill-current" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">ログインエラー</h3>
            <div class="mt-2 text-sm text-red-700">
              {{ errorMessage }}
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 space-y-4">
        <!-- OAuth ログインボタン -->
        <div class="space-y-3">
          <button
            @click="login('google')"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
              <path
                class="fill-current"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                class="fill-current"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                class="fill-current"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              />
              <path
                class="fill-current"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            Google でログイン
          </button>

        </div>

        <div class="text-center">
          <NuxtLink to="/" class="text-sm text-gray-600 hover:text-gray-900">
            ← ホームに戻る
          </NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// ゲストミドルウェア適用、認証レイアウト使用
definePageMeta({
  middleware: 'guest',
  layout: 'auth',
})

// const authStore = useAuthStore() // 将来のauth機能実装時に使用

// エラーハンドリング
const route = useRoute()
const error = ref(false)
const errorMessage = ref('')

if (route.query.error) {
  error.value = true
  switch (route.query.error) {
    case 'oauth_failed':
      errorMessage.value = 'OAuth認証に失敗しました。再度お試しください。'
      break
    default:
      errorMessage.value = 'ログインエラーが発生しました。'
  }
}

// API クライアント
const { $api } = useNuxtApp()

// ログイン処理
const login = (provider: string) => {
  $api.auth.login(provider)
}

// メタデータ設定
useHead({
  title: 'ログイン - マジキチメシ',
  meta: [
    {
      name: 'description',
      content: 'マジキチメシにログインして、吉祥寺地域の店舗ランキングを作成・共有しましょう',
    },
  ],
})
</script>
