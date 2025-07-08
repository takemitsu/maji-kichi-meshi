export default defineNuxtRouteMiddleware((to, from) => {
  const authStore = useAuthStore()
  
  // 未認証の場合はログインページにリダイレクト
  if (!authStore.isLoggedIn) {
    return navigateTo('/login')
  }
})