export default defineNuxtPlugin(() => {
  const authStore = useAuthStore()
  
  // 認証状態の初期化
  authStore.initAuth()
  
  // URL パラメータから認証情報を取得（OAuth コールバック処理）
  const route = useRoute()
  const router = useRouter()
  
  if (route.query.token && route.query.user) {
    try {
      const token = route.query.token as string
      const userData = JSON.parse(decodeURIComponent(route.query.user as string))
      
      authStore.setAuth(userData, token)
      
      // URLパラメータをクリア
      router.replace({ query: {} })
      
      // ダッシュボードにリダイレクト
      navigateTo('/dashboard')
    } catch (error) {
      console.error('OAuth callback error:', error)
      navigateTo('/login?error=oauth_failed')
    }
  }
})