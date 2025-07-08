// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-05-15',
  devtools: { enabled: true },
  
  // SPA設定
  ssr: false,
  
  // Tailwind CSS設定
  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt'
  ],
  
  // TypeScript設定
  typescript: {
    typeCheck: false
  },
  
  // API設定
  runtimeConfig: {
    public: {
      apiBase: process.env.API_BASE_URL || 'http://localhost:8000/api'
    }
  },
  
  // CSS設定
  css: ['~/assets/css/main.css']
})
