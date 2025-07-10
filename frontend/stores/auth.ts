import { defineStore } from 'pinia'
import type { User, AuthState } from '~/types/auth'

interface ExtendedAuthState extends AuthState {
    tokenExpiresAt: number | null
}

export const useAuthStore = defineStore('auth', {
    state: (): ExtendedAuthState => ({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
        error: null,
        tokenExpiresAt: null,
    }),

    getters: {
        isTokenExpired: (state) => {
            if (!state.tokenExpiresAt) return false
            return Date.now() >= state.tokenExpiresAt
        },
        isLoggedIn: (state) => {
            if (!state.isAuthenticated || !state.token) return false
            if (!state.tokenExpiresAt) return true
            return Date.now() < state.tokenExpiresAt
        },
        currentUser: (state) => state.user,
        authToken: (state) => state.token,
        timeUntilExpiry: (state) => {
            if (!state.tokenExpiresAt) return null
            const timeLeft = state.tokenExpiresAt - Date.now()
            return timeLeft > 0 ? timeLeft : 0
        },
    },

    actions: {
        setAuth(user: User, token: string, expiresIn?: number) {
            this.user = user
            this.token = token
            this.isAuthenticated = true
            this.error = null

            // トークンの有効期限を設定（デフォルト1週間）
            const expirationTime = expiresIn || 7 * 24 * 60 * 60 // 1週間（秒）
            this.tokenExpiresAt = Date.now() + expirationTime * 1000

            // LocalStorageに保存
            if (process.client) {
                localStorage.setItem('auth_token', token)
                localStorage.setItem('user', JSON.stringify(user))
                localStorage.setItem('token_expires_at', this.tokenExpiresAt.toString())
            }

            // 自動ログアウトタイマーを設定
            this.setAutoLogoutTimer()
        },

        setError(error: string) {
            this.error = error
            this.isLoading = false
        },

        setLoading(loading: boolean) {
            this.isLoading = loading
            if (loading) {
                this.error = null
            }
        },

        clearAuth() {
            this.user = null
            this.token = null
            this.isAuthenticated = false
            this.error = null
            this.tokenExpiresAt = null

            // 自動ログアウトタイマーをクリア
            this.clearAutoLogoutTimer()

            // LocalStorageからクリア
            if (process.client) {
                localStorage.removeItem('auth_token')
                localStorage.removeItem('user')
                localStorage.removeItem('token_expires_at')
            }
        },

        initAuth() {
            if (process.client) {
                const token = localStorage.getItem('auth_token')
                const userData = localStorage.getItem('user')
                const expiresAt = localStorage.getItem('token_expires_at')

                if (token && userData && expiresAt) {
                    try {
                        const user = JSON.parse(userData)
                        const expirationTime = parseInt(expiresAt)

                        // トークンが期限切れかチェック
                        if (Date.now() >= expirationTime) {
                            console.info('Token has expired, clearing auth')
                            this.clearAuth()
                            return
                        }

                        this.user = user
                        this.token = token
                        this.isAuthenticated = true
                        this.tokenExpiresAt = expirationTime

                        // 自動ログアウトタイマーを設定
                        this.setAutoLogoutTimer()
                    } catch (error) {
                        console.error('Failed to parse auth data:', error)
                        this.clearAuth()
                    }
                }
            }
        },

        // 自動ログアウトタイマー管理
        autoLogoutTimer: null as NodeJS.Timeout | null,

        setAutoLogoutTimer() {
            this.clearAutoLogoutTimer()

            if (this.tokenExpiresAt && process.client) {
                const timeUntilExpiry = this.tokenExpiresAt - Date.now()

                if (timeUntilExpiry > 0) {
                    this.autoLogoutTimer = setTimeout(() => {
                        console.info('Token expired, auto-logout')
                        this.logout()
                    }, timeUntilExpiry)
                }
            }
        },

        clearAutoLogoutTimer() {
            if (this.autoLogoutTimer) {
                clearTimeout(this.autoLogoutTimer)
                this.autoLogoutTimer = null
            }
        },

        checkTokenExpiration() {
            if (this.isTokenExpired) {
                console.info('Token expired during check, logging out')
                this.logout()
                return false
            }
            return true
        },

        async logout() {
            try {
                // バックエンドにログアウト要求（任意）
                const { $api } = useNuxtApp()
                if (this.token) {
                    await $api.auth.logout()
                }
            } catch (error) {
                console.error('Logout API error:', error)
            } finally {
                this.clearAuth()
                await navigateTo('/login')
            }
        },
    },
})
