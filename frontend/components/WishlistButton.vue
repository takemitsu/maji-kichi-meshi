<template>
    <button
        type="button"
        :disabled="isLoading || (status === 'visited' && !allowDeleteVisited)"
        :class="[
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200',
            getButtonClass(),
            (isLoading || !isAuthenticated || (status === 'visited' && !allowDeleteVisited)) && 'opacity-50 cursor-not-allowed',
        ]"
        :title="getButtonTitle()"
        @click="handleToggle">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            :class="['w-4 h-4', inWishlist ? 'fill-current' : 'fill-none stroke-current']"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round">
            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
        </svg>
        <span>{{ getButtonText() }}</span>
    </button>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import type { WishlistStatusResponse } from '~/types/api'

const props = defineProps<{
    shopId: number
    initialStatus?: WishlistStatusResponse
    sourceType: 'review' | 'shop_detail'
    sourceUserId?: number
    sourceReviewId?: number
    allowDeleteVisited?: boolean // 「行った」状態でも削除可能にするか（行きたいリストページ用）
}>()

const emit = defineEmits<{
    added: []
    removed: []
}>()

const api = useApi()
const authStore = useAuthStore()

const isLoading = ref(false)
const inWishlist = ref(props.initialStatus?.in_wishlist ?? false)
const status = ref<'want_to_go' | 'visited' | undefined>(props.initialStatus?.status)

const isAuthenticated = computed(() => authStore.isAuthenticated)

// ボタンのクラスを取得
const getButtonClass = () => {
    if (status.value === 'visited') {
        // 「行った」状態: バッジ表示（ボタン無効）
        return 'bg-green-100 text-green-700'
    }
    if (inWishlist.value) {
        // 「行きたい」状態: 青色（塗りつぶし）
        return 'bg-blue-100 text-blue-700 hover:bg-blue-200'
    }
    // 未登録: グレー（アウトライン）
    return 'bg-gray-100 text-gray-600 hover:bg-gray-200'
}

// ボタンのテキストを取得
const getButtonText = () => {
    if (status.value === 'visited') {
        return '✓ 行った'
    }
    return '行きたい'
}

// ボタンのツールチップを取得
const getButtonTitle = () => {
    if (!isAuthenticated.value) {
        return 'ログインが必要です'
    }
    if (status.value === 'visited' && !props.allowDeleteVisited) {
        return '行ったお店です（リストページから削除できます）'
    }
    if (inWishlist.value) {
        return '行きたいリストから削除'
    }
    return '行きたいリストに追加'
}

// 行きたい状態を取得
const fetchStatus = async () => {
    try {
        const response = await api.wishlists.getStatus(props.shopId)
        inWishlist.value = response.in_wishlist
        status.value = response.status
    } catch (error) {
        console.error('Failed to fetch wishlist status:', error)
    }
}

// 行きたいリストトグル
const handleToggle = async () => {
    if (!isAuthenticated.value) {
        useToastStore().showLoginToast()
        return
    }
    if (isLoading.value) {
        return
    }

    // 「行った」状態で削除が許可されていない場合は何もしない
    if (status.value === 'visited' && !props.allowDeleteVisited) {
        return
    }

    isLoading.value = true

    try {
        if (inWishlist.value) {
            // 削除
            await api.wishlists.remove(props.shopId)
            inWishlist.value = false
            status.value = undefined
            emit('removed')
        } else {
            // 追加
            await api.wishlists.add({
                shop_id: props.shopId,
                source_type: props.sourceType,
                source_user_id: props.sourceUserId,
                source_review_id: props.sourceReviewId,
            })
            inWishlist.value = true
            status.value = 'want_to_go'
            emit('added')
        }
    } catch (error: unknown) {
        console.error('Failed to toggle wishlist:', error)

        const apiError = error as { status?: number; data?: { message?: string } }
        if (apiError.status === 401) {
            alert('ログインが必要です')
        } else if (apiError.status === 409) {
            alert(apiError.data?.message || 'すでに行きたいリストに追加されています')
        } else {
            alert('行きたいリストの処理に失敗しました')
        }
    } finally {
        isLoading.value = false
    }
}

// 初期化時に行きたい状態を取得
onMounted(() => {
    // initialStatusが渡されていない場合のみAPI呼び出し（パフォーマンス最適化）
    if (props.initialStatus === undefined) {
        fetchStatus()
    }
})
</script>
