<template>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- ヘッダー -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">🔖 行きたいリスト</h1>
                <p class="text-gray-600">あなたが行きたい店舗を管理できます</p>
            </div>

            <!-- タブ切り替え -->
            <div class="flex space-x-2 mb-6 border-b border-gray-200">
                <button
                    :class="[
                        'px-4 py-2 font-medium text-sm transition-colors',
                        currentStatus === 'want_to_go'
                            ? 'text-blue-600 border-b-2 border-blue-600'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                    @click="changeStatus('want_to_go')">
                    行きたい
                </button>
                <button
                    :class="[
                        'px-4 py-2 font-medium text-sm transition-colors',
                        currentStatus === 'visited'
                            ? 'text-blue-600 border-b-2 border-blue-600'
                            : 'text-gray-600 hover:text-gray-900',
                    ]"
                    @click="changeStatus('visited')">
                    行った
                </button>
            </div>

            <!-- ソート切り替え（行きたいタブのみ） -->
            <div v-if="currentStatus === 'want_to_go'" class="flex gap-2 mb-4">
                <button
                    :class="[
                        'px-3 py-1.5 text-sm rounded transition-colors',
                        currentSort === 'priority'
                            ? 'bg-blue-100 text-blue-700 font-medium'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                    ]"
                    @click="changeSort('priority')">
                    優先度順
                </button>
                <button
                    :class="[
                        'px-3 py-1.5 text-sm rounded transition-colors',
                        currentSort === 'created_at'
                            ? 'bg-blue-100 text-blue-700 font-medium'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                    ]"
                    @click="changeSort('created_at')">
                    追加日順
                </button>
            </div>

            <!-- ローディング -->
            <div v-if="isLoading && !wishlists.length" class="flex justify-center py-12">
                <LoadingSpinner />
            </div>

            <!-- エラー -->
            <AlertMessage v-else-if="error" type="error" class="mb-6">
                {{ error }}
            </AlertMessage>

            <!-- 行きたいリスト一覧 -->
            <div v-else-if="wishlists.length > 0">
                <!-- 優先度別グルーピング表示（優先度順ソート時のみ） -->
                <template v-if="currentStatus === 'want_to_go' && currentSort === 'priority'">
                    <div v-for="group in groupedByPriority" :key="group.priority" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            {{ group.label }}
                        </h3>
                        <div class="space-y-4">
                            <WishlistCard
                                v-for="wishlist in group.wishlists"
                                :key="wishlist.id"
                                :wishlist="wishlist"
                                :allow-delete="true"
                                @removed="loadWishlists()" />
                        </div>
                    </div>
                </template>

                <!-- 通常リスト表示（追加日順 or 行ったタブ） -->
                <template v-else>
                    <div class="space-y-4">
                        <WishlistCard
                            v-for="wishlist in wishlists"
                            :key="wishlist.id"
                            :wishlist="wishlist"
                            :allow-delete="true"
                            @removed="loadWishlists()" />
                    </div>
                </template>
            </div>

            <!-- 空の状態 -->
            <div v-else class="text-center py-12">
                <div class="text-6xl mb-4">🔖</div>
                <p class="text-gray-600 text-lg mb-2">
                    {{ currentStatus === 'want_to_go' ? 'まだ行きたいお店がありません' : 'まだ行ったお店がありません' }}
                </p>
                <p class="text-gray-500 mb-6">気になる店舗を行きたいリストに追加してみましょう</p>
                <NuxtLink
                    to="/shops"
                    class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    店舗を探す
                </NuxtLink>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Wishlist } from '~/types/api'

definePageMeta({
    middleware: 'auth',
})

useSeoMeta({
    title: '行きたいリスト',
    description: 'あなたが行きたい店舗のリスト',
})

const api = useApi()

const wishlists = ref<Wishlist[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)
const currentStatus = ref<'want_to_go' | 'visited'>('want_to_go')
const currentSort = ref<'priority' | 'created_at'>('priority')

// 優先度別グルーピング（優先度順ソート時）
const groupedByPriority = computed(() => {
    const groups = [
        { priority: 3, label: '── 絶対行きたい ──', wishlists: [] as Wishlist[] },
        { priority: 2, label: '── そのうち行きたい ──', wishlists: [] as Wishlist[] },
        { priority: 1, label: '── いつか行きたい ──', wishlists: [] as Wishlist[] },
    ]

    wishlists.value.forEach((wishlist) => {
        const group = groups.find((g) => g.priority === wishlist.priority)
        if (group) {
            group.wishlists.push(wishlist)
        }
    })

    // 空のグループは表示しない
    return groups.filter((g) => g.wishlists.length > 0)
})

// ウィッシュリスト読み込み
const loadWishlists = async () => {
    isLoading.value = true
    error.value = null

    try {
        const response = await api.wishlists.list({
            status: currentStatus.value,
            sort: currentSort.value,
        })
        wishlists.value = response.data
    } catch (e: unknown) {
        console.error('Failed to load wishlists:', e)
        error.value = '行きたいリストの読み込みに失敗しました'
    } finally {
        isLoading.value = false
    }
}

// タブ切り替え
const changeStatus = (status: 'want_to_go' | 'visited') => {
    currentStatus.value = status
    loadWishlists()
}

// ソート切り替え
const changeSort = (sort: 'priority' | 'created_at') => {
    currentSort.value = sort
    loadWishlists()
}

// 初期読み込み
onMounted(() => {
    loadWishlists()
})
</script>
