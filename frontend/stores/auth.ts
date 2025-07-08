import { defineStore } from 'pinia'

export interface User {
  id: number
  name: string
  email: string
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    token: null,
    isAuthenticated: false
  }),

  getters: {
    isLoggedIn: (state) => state.isAuthenticated && !!state.token,
    currentUser: (state) => state.user,
    authToken: (state) => state.token
  },

  actions: {
    setAuth(user: User, token: string) {
      this.user = user
      this.token = token
      this.isAuthenticated = true
      
      // LocalStorageに保存
      if (process.client) {
        localStorage.setItem('auth_token', token)
        localStorage.setItem('user', JSON.stringify(user))
      }
    },

    clearAuth() {
      this.user = null
      this.token = null
      this.isAuthenticated = false
      
      // LocalStorageからクリア
      if (process.client) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
      }
    },

    initAuth() {
      if (process.client) {
        const token = localStorage.getItem('auth_token')
        const userData = localStorage.getItem('user')
        
        if (token && userData) {
          try {
            const user = JSON.parse(userData)
            this.setAuth(user, token)
          } catch (error) {
            console.error('Failed to parse user data:', error)
            this.clearAuth()
          }
        }
      }
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
    }
  }
})