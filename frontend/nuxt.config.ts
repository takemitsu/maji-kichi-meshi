// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-05-15',
    devtools: { enabled: true },

    // SPA設定
    ssr: false,

    // Tailwind CSS設定
    modules: ['@nuxtjs/tailwindcss', '@pinia/nuxt'],

    // TypeScript設定
    typescript: {
        typeCheck: false,
    },

    // API設定
    runtimeConfig: {
        public: {
            apiBase: process.env.API_BASE_URL || 'http://localhost:8000/api',
        },
    },

    // CSS設定
    css: ['~/assets/css/main.css'],

    // App設定
    app: {
        head: {
            title: 'マジキチメシ - 吉祥寺の個人的な店舗ランキング',
            meta: [
                { charset: 'utf-8' },
                { name: 'viewport', content: 'width=device-width, initial-scale=1' },
                { 
                    name: 'description', 
                    content: '吉祥寺エリアの店舗について、客観的なレビューとは独立した個人的で主観的なランキングを作成・共有するアプリ' 
                },
                { name: 'theme-color', content: '#FF6B35' }
            ],
            link: [
                { rel: 'icon', type: 'image/svg+xml', href: '/favicon.svg' },
                { rel: 'icon', type: 'image/x-icon', href: '/favicon.ico' },
                { rel: 'apple-touch-icon', href: '/apple-touch-icon.png' },
                { rel: 'manifest', href: '/manifest.json' }
            ]
        }
    },
})
