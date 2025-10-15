<template>
    <button
        type="button"
        :disabled="isLoading"
        :class="[
            'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200',
            isLiked
                ? 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
            (isLoading || !isAuthenticated) && 'opacity-50 cursor-not-allowed',
        ]"
        :title="!isAuthenticated ? 'ログインが必要です' : isLiked ? 'いいねを取り消す' : 'いいねする'"
        @click="handleToggle"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            :class="['w-4 h-4', isLiked ? 'fill-current' : 'fill-none stroke-current']"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3" />
        </svg>
        <span>{{ likesCount }}</span>
    </button>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = defineProps<{
    reviewId: number
    initialLikesCount?: number
    initialIsLiked?: boolean
}>()

const emit = defineEmits<{
    liked: [count: number]
    unliked: [count: number]
}>()

const api = useApi()
const authStore = useAuthStore()

const isLoading = ref(false)
const likesCount = ref(props.initialLikesCount ?? 0)
const isLiked = ref(props.initialIsLiked ?? false)

const isAuthenticated = computed(() => authStore.isAuthenticated)

// いいね情報を取得
const fetchLikes = async () => {
    try {
        const response = await api.reviews.getLikes(props.reviewId)
        likesCount.value = response.likes_count
        if (response.is_liked !== undefined) {
            isLiked.value = response.is_liked
        }
    } catch (error) {
        console.error('Failed to fetch likes:', error)
    }
}

// いいねトグル
const handleToggle = async () => {
    if (!isAuthenticated.value) {
        useToastStore().showLoginToast()
        return
    }
    if (isLoading.value) {
        return
    }

    isLoading.value = true

    try {
        const response = await api.reviews.toggleLike(props.reviewId)

        likesCount.value = response.likes_count
        isLiked.value = response.is_liked

        if (response.is_liked) {
            emit('liked', response.likes_count)
        } else {
            emit('unliked', response.likes_count)
        }
    } catch (error: unknown) {
        console.error('Failed to toggle like:', error)

        // エラーメッセージ表示（将来的にトースト通知を実装）
        const apiError = error as { status?: number }
        if (apiError.status === 401) {
            alert('ログインが必要です')
        } else {
            alert('いいねの処理に失敗しました')
        }
    } finally {
        isLoading.value = false
    }
}

// 初期化時にいいね状態を取得
onMounted(() => {
    // initialLikesCountが渡されていない場合のみAPI呼び出し
    if (props.initialLikesCount === undefined) {
        fetchLikes()
    }
})

// 認証状態が変わったらいいね状態を再取得
watch(
    () => authStore.isAuthenticated,
    (newValue) => {
        if (newValue) {
            fetchLikes()
        }
    },
)
</script>
