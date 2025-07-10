export default defineNuxtRouteMiddleware(() => {
    const authStore = useAuthStore()

    // 未認証の場合はログインページにリダイレクト
    if (!authStore.isLoggedIn) {
        return navigateTo('/login')
    }
})
