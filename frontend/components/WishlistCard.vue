<template>
    <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200 p-4 md:p-6">
        <!-- 店舗情報 -->
        <div class="flex items-start gap-4 mb-4">
            <!-- 店舗画像 -->
            <div class="w-20 h-20 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                <template v-if="localWishlist.shop?.images && localWishlist.shop.images.length > 0">
                    <img
                        :src="localWishlist.shop.images[0].urls.thumbnail"
                        :alt="localWishlist.shop.name"
                        class="w-full h-full object-cover" />
                </template>
                <template v-else>
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                        <svg class="w-8 h-8 text-gray-400 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </template>
            </div>

            <!-- 店舗詳細 -->
            <div class="flex-1 min-w-0">
                <NuxtLink
                    :to="`/shops/${localWishlist.shop_id}`"
                    class="text-lg font-semibold text-gray-900 hover:text-blue-600 block mb-1">
                    {{ localWishlist.shop?.name }}
                </NuxtLink>
                <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                    <span v-if="localWishlist.shop?.average_rating">★{{ localWishlist.shop.average_rating.toFixed(1) }}</span>
                    <span v-if="localWishlist.shop?.categories && localWishlist.shop.categories.length > 0">
                        {{ localWishlist.shop.categories.map((c) => c.name).join(', ') }}
                    </span>
                </div>

                <!-- 出典情報 -->
                <div v-if="localWishlist.source_user" class="text-xs text-gray-500">
                    <UserLink :user="localWishlist.source_user" page-type="reviews" custom-class="text-xs" />
                    さんのレビューから
                </div>
                <div v-else-if="localWishlist.source_type === 'shop_detail'" class="text-xs text-gray-500">店舗詳細から</div>
            </div>
        </div>

        <!-- 優先度セレクター（want_to_goステータスのみ） -->
        <div v-if="localWishlist.status === 'want_to_go'" class="mb-4">
            <PrioritySelector
                :shop-id="localWishlist.shop_id"
                v-model="localWishlist.priority"
                :show-label="true"
                @priority-changed="handlePriorityChanged" />
        </div>

        <!-- 訪問日（visitedステータスのみ） -->
        <div v-if="localWishlist.status === 'visited' && localWishlist.visited_at" class="mb-4 text-sm text-gray-600">
            訪問日: {{ formatDate(localWishlist.visited_at) }}
        </div>

        <!-- アクションボタン -->
        <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
            <!-- 「行った」に変更ボタン（want_to_goステータスのみ） -->
            <button
                v-if="localWishlist.status === 'want_to_go'"
                class="px-3 py-1.5 bg-green-100 text-green-700 rounded text-sm font-medium hover:bg-green-200 transition-colors whitespace-nowrap"
                @click="changeToVisited">
                ✓ 行った
            </button>

            <!-- 「行きたい」に戻すボタン（visitedステータスのみ） -->
            <button
                v-if="localWishlist.status === 'visited'"
                class="px-2 py-1.5 bg-gray-100 text-gray-700 rounded text-sm font-medium hover:bg-gray-200 transition-colors whitespace-nowrap flex-shrink-0"
                @click="changeToWantToGo">
                ← 行きたい
            </button>

            <!-- レビューを書くボタン（visitedステータスのみ） -->
            <NuxtLink
                v-if="localWishlist.status === 'visited'"
                :to="`/reviews/create?shop_id=${localWishlist.shop_id}`"
                class="px-2 py-1.5 bg-blue-100 text-blue-700 rounded text-sm font-medium hover:bg-blue-200 transition-colors whitespace-nowrap flex-shrink-0">
                📝 レビューを書く
            </NuxtLink>

            <!-- 削除ボタン -->
            <button
                v-if="allowDelete"
                class="px-3 py-1.5 bg-red-100 text-red-700 rounded text-sm font-medium hover:bg-red-200 transition-colors whitespace-nowrap ml-auto"
                @click="confirmRemove">
                削除
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Wishlist } from '~/types/api'

const props = defineProps<{
    wishlist: Wishlist
    allowDelete?: boolean
}>()

const emit = defineEmits<{
    removed: []
    statusChanged: []
}>()

const api = useApi()

// ローカルの状態管理（楽観的UI用）
const localWishlist = ref<Wishlist>({ ...props.wishlist })

// propsが変更されたらローカルも更新
watch(() => props.wishlist, (newVal) => {
    localWishlist.value = { ...newVal }
}, { deep: true })

// 日付フォーマット
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ja-JP')
}

// 優先度変更
const handlePriorityChanged = () => {
    // PrioritySelectorコンポーネント内でAPI呼び出しを行うため、ここでは何もしない
}

// 「行った」に変更
const changeToVisited = async () => {
    const originalStatus = localWishlist.value.status
    const originalVisitedAt = localWishlist.value.visited_at

    // 即座にローカルで状態を変更
    localWishlist.value.status = 'visited'
    localWishlist.value.visited_at = new Date().toISOString().split('T')[0]

    try {
        await api.wishlists.updateStatus(props.wishlist.shop_id, { status: 'visited' })
        emit('statusChanged')
    } catch (error) {
        console.error('Failed to change status:', error)
        // エラー時は元に戻す
        localWishlist.value.status = originalStatus
        localWishlist.value.visited_at = originalVisitedAt
        alert('ステータスの変更に失敗しました')
    }
}

// 「行きたい」に戻す
const changeToWantToGo = async () => {
    const originalStatus = localWishlist.value.status

    // 即座にローカルで状態を変更
    localWishlist.value.status = 'want_to_go'

    try {
        await api.wishlists.updateStatus(props.wishlist.shop_id, { status: 'want_to_go' })
        emit('statusChanged')
    } catch (error) {
        console.error('Failed to change status:', error)
        // エラー時は元に戻す
        localWishlist.value.status = originalStatus
        alert('ステータスの変更に失敗しました')
    }
}

// 削除確認
const confirmRemove = () => {
    if (confirm('本当に削除しますか？')) {
        removeWishlist()
    }
}

// 削除
const removeWishlist = async () => {
    try {
        await api.wishlists.remove(props.wishlist.shop_id)
        emit('removed')
    } catch (error) {
        console.error('Failed to remove wishlist:', error)
        alert('削除に失敗しました')
    }
}
</script>
