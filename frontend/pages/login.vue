<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8"
  >
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
            <svg
              class="h-5 w-5 text-red-400"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
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
                fill="currentColor"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
              />
              <path
                fill="currentColor"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
              />
              <path
                fill="currentColor"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
              />
              <path
                fill="currentColor"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
              />
            </svg>
            Google でログイン
          </button>

          <button
            @click="login('github')"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
              />
            </svg>
            GitHub でログイン
          </button>

          <button
            @click="login('line')"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M12 0C5.373 0 0 4.975 0 11.111c0 2.47.912 4.727 2.414 6.533-.141-.499-.266-1.144-.266-1.144l-1.474-7.056A.5.5 0 01.84 8.92l2.064-.413a.5.5 0 01.584.325l1.17 3.845c.06.196.295.307.49.246l2.396-.744a.5.5 0 01.584.325l.94 3.096c.06.196.295.307.49.246l2.396-.744a.5.5 0 01.584.325l.94 3.096c.06.196.295.307.49.246l2.396-.744a.5.5 0 01.584.325l1.17 3.845c.088.289.446.417.696.248C22.412 15.838 24 13.627 24 11.111 24 4.975 18.627 0 12 0z"
              />
            </svg>
            LINE でログイン
          </button>

          <button
            @click="login('twitter')"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-400 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"
              />
            </svg>
            Twitter でログイン
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

const authStore = useAuthStore()

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
      content:
        'マジキチメシにログインして、吉祥寺地域の店舗ランキングを作成・共有しましょう',
    },
  ],
})
</script>
