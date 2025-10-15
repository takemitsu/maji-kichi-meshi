import type { User } from '~/types/auth'
import type {
    ApiResponse,
    PaginatedResponse,
    Shop,
    Category,
    Review,
    Ranking,
    RankingCreateRequest,
    RankingUpdateRequest,
    ReviewImage,
    ShopImage,
    ErrorResponse,
    ReviewLikesResponse,
    ReviewLikeToggleResponse,
    Wishlist,
    WishlistStatusResponse,
    WishlistAddRequest,
    WishlistAddResponse,
    WishlistUpdatePriorityRequest,
    WishlistUpdateStatusRequest,
    DashboardStatsResponse,
} from '~/types/api'

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

            const error = new Error(`API Error: ${response.status}`) as Error & { status: number; data: ErrorResponse | null }
            error.status = response.status
            error.data = errorData

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
            list: (params?: Record<string, string | number>) => {
                const query = params
                    ? `?${new URLSearchParams(
                          Object.entries(params).reduce(
                              (acc, [key, value]) => {
                                  acc[key] = String(value)
                                  return acc
                              },
                              {} as Record<string, string>,
                          ),
                      ).toString()}`
                    : ''
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

            // 画像アップロード
            uploadImages: (shopId: number, formData: FormData) =>
                apiFetch<ApiResponse<{ images: ShopImage[] }>>(`/shops/${shopId}/images`, {
                    method: 'POST',
                    body: formData,
                }),

            // 画像削除
            deleteImage: (shopId: number, imageId: number) =>
                apiFetch<{ message: string }>(`/shops/${shopId}/images/${imageId}`, { method: 'DELETE' }),
        },

        // カテゴリ関連
        categories: {
            list: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Category>>(`/categories${query}`)
            },

            get: (id: number) => apiFetch<ApiResponse<Category>>(`/categories/${id}`),

            create: (data: Partial<Category>) =>
                apiFetch<ApiResponse<Category>>('/categories', {
                    method: 'POST',
                    body: JSON.stringify(data),
                }),

            update: (id: number, data: Partial<Category>) =>
                apiFetch<ApiResponse<Category>>(`/categories/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data),
                }),

            delete: (id: number) => apiFetch<{ message: string }>(`/categories/${id}`, { method: 'DELETE' }),
        },

        // レビュー関連
        reviews: {
            list: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Review>>(`/reviews${query}`)
            },

            myReviews: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Review>>(`/my-reviews${query}`)
            },

            get: (id: number) => apiFetch<ApiResponse<Review>>(`/reviews/${id}`),

            create: (data: Partial<Review>) =>
                apiFetch<ApiResponse<Review>>('/reviews', {
                    method: 'POST',
                    body: JSON.stringify(data),
                }),

            update: (id: number, data: Partial<Review>) =>
                apiFetch<ApiResponse<Review>>(`/reviews/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data),
                }),

            delete: (id: number) => apiFetch<{ message: string }>(`/reviews/${id}`, { method: 'DELETE' }),

            // 画像管理
            uploadImages: (reviewId: number, files: File[]) => {
                const formData = new FormData()
                files.forEach((file) => {
                    formData.append('images[]', file)
                })

                return apiFetch<ApiResponse<{ images: ReviewImage[] }>>(`/reviews/${reviewId}/images`, {
                    method: 'POST',
                    body: formData,
                    headers: {}, // FormDataの場合、Content-Typeヘッダーは自動設定
                })
            },

            deleteImage: (reviewId: number, imageId: number) =>
                apiFetch<{ message: string }>(`/reviews/${reviewId}/images/${imageId}`, {
                    method: 'DELETE',
                }),

            // いいね機能
            getLikes: (reviewId: number) => apiFetch<ReviewLikesResponse>(`/reviews/${reviewId}/likes`),

            toggleLike: (reviewId: number) =>
                apiFetch<ReviewLikeToggleResponse>(`/reviews/${reviewId}/like`, {
                    method: 'POST',
                }),

            myLikedReviews: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Review>>(`/my-liked-reviews${query}`)
            },
        },

        // ランキング関連
        rankings: {
            list: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Ranking>>(`/rankings${query}`)
            },

            publicRankings: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Ranking>>(`/public-rankings${query}`)
            },

            myRankings: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<PaginatedResponse<Ranking>>(`/my-rankings${query}`)
            },

            get: (id: number) => apiFetch<ApiResponse<Ranking>>(`/rankings/${id}`),

            create: (data: RankingCreateRequest) =>
                apiFetch<ApiResponse<Ranking>>('/rankings', {
                    method: 'POST',
                    body: JSON.stringify(data),
                }),

            update: (id: number, data: RankingUpdateRequest) =>
                apiFetch<ApiResponse<Ranking>>(`/rankings/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data),
                }),

            delete: (id: number) => apiFetch<{ message: string }>(`/rankings/${id}`, { method: 'DELETE' }),
        },

        // 統計情報
        stats: {
            dashboard: () => apiFetch<ApiResponse<DashboardStatsResponse>>('/stats/dashboard'),
        },

        // ユーザー関連
        users: {
            info: (id: number) => apiFetch<User>(`/users/${id}/info`),
        },

        // プロフィール関連
        profile: {
            // プロフィール情報取得
            get: () => apiFetch<ApiResponse<User>>('/profile'),

            // プロフィール情報更新
            update: (data: { name?: string; email?: string }) =>
                apiFetch<ApiResponse<User>>('/profile', {
                    method: 'PUT',
                    body: JSON.stringify(data),
                }),

            // プロフィール画像アップロード
            uploadImage: (file: File) => {
                const formData = new FormData()
                formData.append('profile_image', file)
                return apiFetch<ApiResponse<{ profile_image: { urls: Record<string, string>; uploaded_at: string } }>>(
                    '/profile/image',
                    {
                        method: 'POST',
                        body: formData,
                    },
                )
            },

            // プロフィール画像削除
            deleteImage: () => apiFetch<{ message: string }>('/profile/image', { method: 'DELETE' }),

            // プロフィール画像URL取得
            getImageUrl: (size: string = 'medium') =>
                apiFetch<ApiResponse<{ url: string; size: string }>>(`/profile/image-url?size=${size}`),
        },

        // 行きたいリスト関連
        wishlists: {
            // 特定店舗の行きたい状態取得
            getStatus: (shopId: number) => apiFetch<WishlistStatusResponse>(`/shops/${shopId}/wishlist-status`),

            // 行きたいリストに追加
            add: (data: WishlistAddRequest) =>
                apiFetch<WishlistAddResponse>('/my-wishlist', {
                    method: 'POST',
                    body: JSON.stringify(data),
                }),

            // 行きたいリストから削除
            remove: (shopId: number) =>
                apiFetch<{ message: string }>(`/my-wishlist/${shopId}`, {
                    method: 'DELETE',
                }),

            // 優先度変更
            updatePriority: (shopId: number, data: WishlistUpdatePriorityRequest) =>
                apiFetch<ApiResponse<Wishlist>>(`/my-wishlist/${shopId}/priority`, {
                    method: 'PATCH',
                    body: JSON.stringify(data),
                }),

            // ステータス変更
            updateStatus: (shopId: number, data: WishlistUpdateStatusRequest) =>
                apiFetch<ApiResponse<Wishlist>>(`/my-wishlist/${shopId}/status`, {
                    method: 'PATCH',
                    body: JSON.stringify(data),
                }),

            // 行きたいリスト取得
            list: (params?: Record<string, string | number>) => {
                const query = params ? `?${new URLSearchParams(params as Record<string, string>).toString()}` : ''
                return apiFetch<ApiResponse<Wishlist[]>>(`/my-wishlist${query}`)
            },
        },
    }

    return api
}
