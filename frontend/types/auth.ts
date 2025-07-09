// 認証関連の型定義

export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  email_verified_at?: string
  created_at?: string
  updated_at?: string
}

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
}

export interface AuthResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: User
}

export interface OAuthCallbackParams {
  access_token?: string
  token_type?: string
  expires_in?: string
  user_id?: string
  user_name?: string
  user_email?: string
  success?: string
  error?: string
  error_description?: string
}

export interface LoginError {
  error: string
  error_description?: string
}

export type AuthProvider = 'google' | 'github' | 'line' | 'twitter'
