export default defineNuxtPlugin(() => {
  const authStore = useAuthStore()

  // 認証状態の初期化
  authStore.initAuth()

  // OAuth コールバック処理はauth/callback.vueページで処理するため、
  // このプラグインでは初期化のみ行う
})
