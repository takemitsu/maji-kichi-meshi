// API関連の型定義

export interface ApiResponse<T> {
    data: T
    message?: string
}

export interface PaginatedResponse<T> {
    data: T[]
    links: {
        first: string
        last: string
        prev?: string
        next?: string
    }
    meta: {
        current_page: number
        from?: number
        last_page: number
        links: Array<{
            url?: string
            label: string
            active: boolean
        }>
        path: string
        per_page: number
        to?: number
        total: number
    }
}

export interface Shop {
    id: number
    name: string
    description?: string
    address: string
    latitude: string
    longitude: string
    phone?: string
    website?: string
    google_place_id?: string
    is_closed: boolean
    status: string
    average_rating: number
    review_count: number
    categories: Category[]
    images?: ShopImage[]
    distance?: number
    created_at: string
    updated_at: string
    // 動的プロパティ（検索時のハイライト、画像URL等）
    image_url?: string
    highlightedName?: string
    highlightedAddress?: string
}

export interface Category {
    id: number
    name: string
    slug: string
    type: 'basic' | 'time' | 'ranking'
    shops_count?: number
    created_at: string
    updated_at: string
}

export interface Review {
    id: number
    rating: number
    repeat_intention: string
    repeat_intention_text: string
    memo?: string
    visited_at: string
    has_images: boolean
    images: ReviewImage[]
    user: {
        id: number
        name: string
    }
    shop: {
        id: number
        name: string
        address?: string
        image_url?: string
    }
    created_at: string
    updated_at: string
}

export interface ReviewImage {
    id: number
    filename: string
    original_name: string
    urls: {
        thumbnail: string
        small: string
        medium: string
        large: string
    }
    file_size: number
    mime_type: string
    created_at: string
    updated_at: string
}

export interface ShopImage {
    id: number
    uuid: string
    original_name: string
    urls: {
        thumbnail: string
        small: string
        medium: string
        large: string
    }
    file_size: number
    mime_type: string
    sort_order: number
    created_at: string
    updated_at: string
}

export interface Ranking {
    id: number
    rank_position: number
    title?: string
    description?: string
    is_public: boolean
    user: {
        id: number
        name: string
        email: string
        avatar?: string
        created_at: string
        updated_at: string
    }
    shop: Shop
    category: Category
    created_at: string
    updated_at: string
    // 集計プロパティ
    shops_count?: number
}

export interface ErrorResponse {
    error: string
    message?: string
    messages?: Record<string, string[]>
}
