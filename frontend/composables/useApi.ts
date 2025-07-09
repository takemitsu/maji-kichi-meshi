import type { User } from '~/types/auth'
import type { ApiResponse, PaginatedResponse, Shop } from '~/types/api'

export const useApi = () => {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()

  const apiBase = config.public.apiBase

  // 基本的なFetch関数
  const apiFetch = async <T>(url: string, options: RequestInit = {}): Promise<T> => {
    const headers: Record<string, string> = {
      Accept: 'application/json',
      ...(options.headers as Record<string, string>),
    }

    // FormDataでない場合のみContent-Typeを設定
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json'
    }

    // 認証トークンがある場合は追加
    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`
    }

    const response = await fetch(`${apiBase}${url}`, {
      ...options,
      headers,
    })

    if (!response.ok) {
      if (response.status === 401) {
        // 認証エラーの場合、ログアウト処理
        authStore.clearAuth()
        await navigateTo('/login')
      }

      // エラーレスポンスの詳細を取得
      let errorData = null
      try {
        errorData = await response.json()
      } catch {
        // JSONパースに失敗した場合
      }

      const error = new Error(`API Error: ${response.status}`)
      ;(error as any).status = response.status
      ;(error as any).data = errorData

      throw error
    }

    return response.json()
  }

  // API関数群
  const api = {
    // 認証関連
    auth: {
      // OAuth認証開始
      login: (provider: string) => {
        window.location.href = `${apiBase}/auth/${provider}`
      },

      // ログアウト
      logout: () => apiFetch('/auth/logout', { method: 'POST' }),

      // ユーザー情報取得
      me: () => apiFetch<ApiResponse<User>>('/auth/me'),
    },

    // 店舗関連
    shops: {
      list: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<PaginatedResponse<Shop>>(`/shops${query}`)
      },

      get: (id: number) => apiFetch<ApiResponse<Shop>>(`/shops/${id}`),

      create: (data: Partial<Shop>) =>
        apiFetch<ApiResponse<Shop>>('/shops', {
          method: 'POST',
          body: JSON.stringify(data),
        }),

      update: (id: number, data: Partial<Shop>) =>
        apiFetch<ApiResponse<Shop>>(`/shops/${id}`, {
          method: 'PUT',
          body: JSON.stringify(data),
        }),

      delete: (id: number) => apiFetch<{ message: string }>(`/shops/${id}`, { method: 'DELETE' }),
    },

    // カテゴリ関連
    categories: {
      list: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/categories${query}`)
      },

      get: (id: number) => apiFetch<{ data: any }>(`/categories/${id}`),

      create: (data: any) =>
        apiFetch<{ data: any }>('/categories', {
          method: 'POST',
          body: JSON.stringify(data),
        }),

      update: (id: number, data: any) =>
        apiFetch<{ data: any }>(`/categories/${id}`, {
          method: 'PUT',
          body: JSON.stringify(data),
        }),

      delete: (id: number) => apiFetch(`/categories/${id}`, { method: 'DELETE' }),
    },

    // レビュー関連
    reviews: {
      list: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/reviews${query}`)
      },

      myReviews: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/my-reviews${query}`)
      },

      get: (id: number) => apiFetch<{ data: any }>(`/reviews/${id}`),

      create: (data: any) =>
        apiFetch<{ data: any }>('/reviews', {
          method: 'POST',
          body: JSON.stringify(data),
        }),

      update: (id: number, data: any) =>
        apiFetch<{ data: any }>(`/reviews/${id}`, {
          method: 'PUT',
          body: JSON.stringify(data),
        }),

      delete: (id: number) => apiFetch(`/reviews/${id}`, { method: 'DELETE' }),

      // 画像管理
      uploadImages: (reviewId: number, files: File[]) => {
        const formData = new FormData()
        files.forEach((file, index) => {
          formData.append(`images[${index}]`, file)
        })

        return apiFetch<{ data: any }>(`/reviews/${reviewId}/images`, {
          method: 'POST',
          body: formData,
          headers: {}, // FormDataの場合、Content-Typeヘッダーは自動設定
        })
      },

      deleteImage: (reviewId: number, imageId: number) =>
        apiFetch(`/reviews/${reviewId}/images/${imageId}`, {
          method: 'DELETE',
        }),
    },

    // ランキング関連
    rankings: {
      list: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/rankings${query}`)
      },

      publicRankings: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/public-rankings${query}`)
      },

      myRankings: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/my-rankings${query}`)
      },

      get: (id: number) => apiFetch<{ data: any }>(`/rankings/${id}`),

      create: (data: any) =>
        apiFetch<{ data: any }>('/rankings', {
          method: 'POST',
          body: JSON.stringify(data),
        }),

      update: (id: number, data: any) =>
        apiFetch<{ data: any }>(`/rankings/${id}`, {
          method: 'PUT',
          body: JSON.stringify(data),
        }),

      delete: (id: number) => apiFetch(`/rankings/${id}`, { method: 'DELETE' }),
    },
  }

  return api
}
