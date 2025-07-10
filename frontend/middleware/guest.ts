export default defineNuxtRouteMiddleware(() => {
    const authStore = useAuthStore()

    // 認証済みの場合はダッシュボードにリダイレクト
    if (authStore.isLoggedIn) {
        return navigateTo('/dashboard')
    }
})
