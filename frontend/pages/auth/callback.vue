<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <!-- ローディング状態 -->
        <div v-if="isProcessing" class="space-y-4">
          <LoadingSpinner class="mx-auto" />
          <h2 class="text-2xl font-bold text-gray-900">認証処理中...</h2>
          <p class="text-gray-600">少々お待ちください</p>
        </div>

        <!-- エラー状態 -->
        <div v-else-if="error" class="space-y-4">
          <div
            class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100"
          >
            <svg
              class="h-6 w-6 text-red-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-red-900">認証に失敗しました</h2>
          <p class="text-red-600">
            {{ error }}
          </p>
          <div class="pt-4">
            <NuxtLink
              to="/login"
              class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition duration-200"
            >
              ログインページに戻る
            </NuxtLink>
          </div>
        </div>

        <!-- 成功状態（一瞬表示される） -->
        <div v-else class="space-y-4">
          <div
            class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100"
          >
            <svg
              class="h-6 w-6 text-green-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-green-900">認証が完了しました</h2>
          <p class="text-green-600">ダッシュボードにリダイレクト中...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 認証不要のページ
definePageMeta({
  layout: 'auth',
  middleware: [],
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isProcessing = ref(true)
const error = ref<string | null>(null)

// OAuth コールバック処理
onMounted(async () => {
  try {
    // URLパラメータから認証情報を取得
    const {
      access_token,
      expires_in,
      user_id,
      user_name,
      user_email,
      success,
      error: authError,
      error_description,
    } = route.query

    // エラーケースの処理
    if (authError || success === 'false') {
      throw new Error(
        (error_description as string) || 'OAuth認証に失敗しました'
      )
    }

    // 必須パラメータの確認
    if (!access_token || !user_id || !user_name || !user_email) {
      throw new Error('認証情報が不完全です')
    }

    // ユーザーデータの構築
    const userData = {
      id: parseInt(user_id as string),
      name: user_name as string,
      email: user_email as string,
    }

    // 認証情報をストアに保存（有効期限も含める）
    const expiresInSeconds = expires_in
      ? parseInt(expires_in as string)
      : undefined
    authStore.setAuth(userData, access_token as string, expiresInSeconds)

    // URLパラメータをクリア
    await router.replace({ query: {} })

    // 少し待ってからリダイレクト
    await new Promise(resolve => setTimeout(resolve, 1000))

    // ダッシュボードにリダイレクト
    await navigateTo('/dashboard')
  } catch (err) {
    console.error('OAuth callback error:', err)
    error.value =
      err instanceof Error ? err.message : '予期しないエラーが発生しました'
  } finally {
    isProcessing.value = false
  }
})

// ページタイトル
useHead({
  title: 'ログイン中... - マジキチメシ',
})
</script>
