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
  average_rating: number
  review_count: number
  categories: Category[]
  distance?: number
  created_at: string
  updated_at: string
}

export interface Category {
  id: number
  name: string
  slug: string
  type: 'basic' | 'time' | 'ranking'
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
  }
  created_at: string
  updated_at: string
}

export interface ReviewImage {
  id: number
  file_path: string
  file_size: number
  mime_type: string
  original_name: string
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
}

export interface ErrorResponse {
  error: string
  message?: string
  messages?: Record<string, string[]>
}
