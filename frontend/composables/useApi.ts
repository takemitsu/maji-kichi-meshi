export const useApi = () => {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()

  const apiBase = config.public.apiBase

  // 基本的なFetch関数
  const apiFetch = async <T>(
    url: string,
    options: RequestInit = {}
  ): Promise<T> => {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers
    }

    // 認証トークンがある場合は追加
    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`
    }

    const response = await fetch(`${apiBase}${url}`, {
      ...options,
      headers
    })

    if (!response.ok) {
      if (response.status === 401) {
        // 認証エラーの場合、ログアウト処理
        authStore.clearAuth()
        await navigateTo('/login')
      }
      throw new Error(`API Error: ${response.status}`)
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
      me: () => apiFetch<{ user: any }>('/auth/me')
    },

    // 店舗関連
    shops: {
      list: (params?: Record<string, any>) => {
        const query = params ? `?${new URLSearchParams(params).toString()}` : ''
        return apiFetch<{ data: any[] }>(`/shops${query}`)
      },
      
      get: (id: number) => apiFetch<{ data: any }>(`/shops/${id}`),
      
      create: (data: any) => apiFetch<{ data: any }>('/shops', {
        method: 'POST',
        body: JSON.stringify(data)
      }),
      
      update: (id: number, data: any) => apiFetch<{ data: any }>(`/shops/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data)
      }),
      
      delete: (id: number) => apiFetch(`/shops/${id}`, { method: 'DELETE' })
    },

    // カテゴリ関連
    categories: {
      list: () => apiFetch<{ data: any[] }>('/categories'),
      
      get: (id: number) => apiFetch<{ data: any }>(`/categories/${id}`),
      
      create: (data: any) => apiFetch<{ data: any }>('/categories', {
        method: 'POST',
        body: JSON.stringify(data)
      })
    },

    // レビュー関連
    reviews: {
      list: (shopId?: number) => {
        const query = shopId ? `?shop_id=${shopId}` : ''
        return apiFetch<{ data: any[] }>(`/reviews${query}`)
      },
      
      get: (id: number) => apiFetch<{ data: any }>(`/reviews/${id}`),
      
      create: (data: any) => apiFetch<{ data: any }>('/reviews', {
        method: 'POST',
        body: JSON.stringify(data)
      }),
      
      update: (id: number, data: any) => apiFetch<{ data: any }>(`/reviews/${id}`, {
        method: 'PUT',
        body: JSON.stringify(data)
      }),
      
      delete: (id: number) => apiFetch(`/reviews/${id}`, { method: 'DELETE' })
    },

    // ランキング関連
    rankings: {
      list: (categoryId?: number) => {
        const query = categoryId ? `?category_id=${categoryId}` : ''
        return apiFetch<{ data: any[] }>(`/rankings${query}`)
      },
      
      update: (data: any) => apiFetch<{ data: any }>('/rankings', {
        method: 'PUT',
        body: JSON.stringify(data)
      })
    }
  }

  return api
}