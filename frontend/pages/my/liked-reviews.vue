<template>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- ヘッダー -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">👍 いいねしたレビュー</h1>
                <p class="text-gray-600">あなたがいいねしたレビューの一覧です</p>
            </div>

            <!-- ローディング -->
            <div v-if="isLoading && !reviews.length" class="flex justify-center py-12">
                <LoadingSpinner />
            </div>

            <!-- エラー -->
            <AlertMessage v-else-if="error" type="error" class="mb-6">
                {{ error }}
            </AlertMessage>

            <!-- レビュー一覧 -->
            <div v-else-if="reviews.length > 0" class="space-y-4 md:space-y-6">
                <div v-for="review in reviews" :key="review.id" class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200 p-3 md:p-6">
                    <!-- ユーザー情報 -->
                    <div class="flex items-center space-x-2 text-sm mb-2">
                        <span class="text-gray-500">{{ formatDate(review.visited_at) }}</span>
                        <span class="flex items-center text-gray-700">
                            by
                            <UserAvatar
                                :user-name="review.user.name"
                                :profile-image-url="review.user.profile_image?.urls?.small"
                                size="xs"
                                class="mx-1" />
                            <UserLink :user="review.user" page-type="reviews" custom-class="text-sm font-medium" />
                        </span>
                    </div>

                    <!-- 店舗情報 -->
                    <NuxtLink
                        :to="`/shops/${review.shop.id}`"
                        class="text-lg font-semibold text-gray-900 hover:text-blue-600 mb-2 block"
                    >
                        {{ review.shop.name }}
                    </NuxtLink>

                    <!-- 評価 -->
                    <div class="flex items-center gap-4 mb-2">
                        <div class="flex items-center space-x-1">
                            <div class="flex">
                                <svg
                                    v-for="star in 5"
                                    :key="star"
                                    class="w-4 h-4 fill-current"
                                    :class="star <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                                    viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600 ml-1">{{ review.rating }}/5</span>
                        </div>
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-800': review.repeat_intention === 'yes',
                                'bg-yellow-100 text-yellow-800': review.repeat_intention === 'maybe',
                                'bg-red-100 text-red-800': review.repeat_intention === 'no',
                                'bg-gray-100 text-gray-800': !['yes', 'maybe', 'no'].includes(review.repeat_intention),
                            }">
                            {{ review.repeat_intention_text }}
                        </span>
                    </div>

                    <!-- コメント（省略表示） -->
                    <div v-if="review.memo" class="mb-2">
                        <p class="text-gray-900 text-sm leading-relaxed line-clamp-2">
                            {{ review.memo }}
                        </p>
                    </div>

                    <!-- 画像（コンパクト） -->
                    <div v-if="review.images && review.images.length > 0" class="mb-2">
                        <div class="flex items-center space-x-2">
                            <div
                                v-for="image in review.images.slice(0, 3)"
                                :key="image.id"
                                class="w-16 h-16 bg-gray-200 rounded-lg overflow-hidden cursor-pointer hover:opacity-90 transition-opacity flex-shrink-0">
                                <img
                                    :src="image.urls.thumbnail"
                                    :alt="image.original_name"
                                    class="w-full h-full object-cover" />
                            </div>
                            <div v-if="review.images.length > 3" class="text-xs text-gray-700">
                                +{{ review.images.length - 3 }}枚
                            </div>
                        </div>
                    </div>

                    <!-- いいねボタン -->
                    <div class="flex items-center gap-4 pt-3 border-t">
                        <LikeButton :review-id="review.id" :initial-likes-count="review.likes_count || 0" :initial-is-liked="review.is_liked ?? true" />
                        <NuxtLink
                            :to="`/reviews/${review.id}`"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                        >
                            レビュー詳細を見る →
                        </NuxtLink>
                    </div>
                </div>

                <!-- ページネーション -->
                <PaginationComponent
                    v-if="pagination.total > pagination.per_page"
                    :current-page="pagination.current_page"
                    :total-pages="pagination.last_page"
                    :total-items="pagination.total"
                    :per-page="pagination.per_page"
                    @page-change="loadPage"
                />
            </div>

            <!-- 空の状態 -->
            <div v-else class="text-center py-12">
                <div class="text-6xl mb-4">👍</div>
                <p class="text-gray-600 text-lg mb-2">まだいいねしたレビューがありません</p>
                <p class="text-gray-500 mb-6">気になるレビューに「いいね」してみましょう</p>
                <NuxtLink
                    to="/reviews"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    レビューを見る
                </NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Review } from '~/types/api'

definePageMeta({
    middleware: 'auth',
})

useSeoMeta({
    title: 'いいねしたレビュー',
    description: 'あなたがいいねしたレビューの一覧',
})

const api = useApi()

const reviews = ref<Review[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
})

// 日付フォーマット
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ja-JP')
}

// レビュー読み込み
const loadReviews = async (page: number = 1) => {
    isLoading.value = true
    error.value = null

    try {
        const response = await api.reviews.myLikedReviews({ page, per_page: 15 })
        reviews.value = response.data
        pagination.value = {
            current_page: response.meta.current_page,
            last_page: response.meta.last_page,
            per_page: response.meta.per_page,
            total: response.meta.total,
        }
    } catch (e: unknown) {
        console.error('Failed to load liked reviews:', e)
        error.value = 'いいねしたレビューの読み込みに失敗しました'
    } finally {
        isLoading.value = false
    }
}

// ページ変更
const loadPage = (page: number) => {
    loadReviews(page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

// 初期読み込み
onMounted(() => {
    loadReviews()
})
</script>
