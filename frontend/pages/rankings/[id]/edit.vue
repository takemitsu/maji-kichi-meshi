<template>
    <div class="mx-auto max-w-4xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- ローディング -->
            <LoadingSpinner v-if="loading" fullscreen />

            <!-- エラーメッセージ -->
            <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

            <!-- フォーム -->
            <div v-if="!loading">
                <!-- ブレッドクラム -->
                <nav class="flex mb-6" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-4">
                        <li>
                            <NuxtLink to="/rankings" class="text-gray-700 hover:text-gray-700">マイランキング</NuxtLink>
                        </li>
                        <li>
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400 fill-current" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </li>
                        <li>
                            <NuxtLink v-if="form.title" :to="`/rankings/${rankingId}`" class="text-gray-700 hover:text-gray-700">
                                {{ form.title }}
                            </NuxtLink>
                        </li>
                        <li>
                            <svg class="flex-shrink-0 h-5 w-5 text-gray-400 fill-current" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </li>
                        <li class="text-gray-900 font-medium">編集</li>
                    </ol>
                </nav>

                <!-- ヘッダー -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                        ランキングを編集
                    </h1>
                    <p class="mt-1 text-sm text-gray-700">ランキングの基本情報と店舗の順序を更新します</p>
                </div>

                <!-- フォーム -->
                <form @submit.prevent="submitRanking" class="space-y-6">
                    <!-- 基本情報 -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">基本情報</h3>

                        <div class="space-y-4">
                            <!-- タイトル -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ランキング名
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="input-field"
                                    placeholder="俺の吉祥寺ラーメンランキング"
                                    maxlength="100"
                                    required />
                                <p class="mt-1 text-sm text-gray-700">{{ form.title.length }}/100 文字</p>
                            </div>

                            <!-- 説明 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">説明（任意）</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="input-field"
                                    placeholder="このランキングの特徴や選定基準を説明してください..."
                                    maxlength="500"></textarea>
                                <p class="mt-1 text-sm text-gray-700">{{ (form.description || '').length }}/500 文字</p>
                            </div>

                            <!-- カテゴリ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">カテゴリ</label>
                                <select v-model="form.category_id" class="input-field">
                                    <option value="" disabled>カテゴリを選択してください</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- 公開設定 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">公開設定</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_public"
                                            :value="false"
                                            type="radio"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                                        <span class="ml-2 text-sm text-gray-900">非公開（自分だけ）</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.is_public"
                                            :value="true"
                                            type="radio"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                                        <span class="ml-2 text-sm text-gray-900">公開（みんなが見られる）</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 店舗選択 -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            店舗選択
                            <span class="text-sm font-normal text-gray-700">（{{ selectedShops.length }}店舗選択済み）</span>
                        </h3>

                        <!-- 店舗検索 -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">店舗を検索して追加</label>
                            <div class="relative">
                                <input
                                    v-model="shopSearchQuery"
                                    @input="handleShopSearch"
                                    type="text"
                                    placeholder="検索..."
                                    class="input-field" />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- 検索結果 -->
                        <div
                            v-if="searchResults.length > 0"
                            class="mb-6 max-h-64 overflow-y-auto border border-gray-200 rounded-md">
                            <div
                                v-for="shop in searchResults"
                                :key="shop.id"
                                class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ shop.name }}</h4>
                                    <p class="text-sm text-gray-600">{{ shop.address }}</p>
                                </div>
                                <button
                                    @click="addShop(shop)"
                                    :disabled="isShopSelected(shop.id)"
                                    type="button"
                                    class="btn-primary text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ isShopSelected(shop.id) ? '追加済み' : '追加' }}
                                </button>
                            </div>
                        </div>

                        <!-- 店舗が見つからない場合 -->
                        <div
                            v-if="shopSearchQuery && searchResults.length === 0 && !searchLoading"
                            class="mb-6 text-center py-4 text-gray-700">
                            <p class="text-sm">店舗が見つかりませんでした</p>
                            <NuxtLink to="/shops" class="text-sm text-blue-600 hover:text-blue-800">
                                新しい店舗を登録する
                            </NuxtLink>
                        </div>

                        <!-- 選択済み店舗一覧 -->
                        <div v-if="selectedShops.length > 0">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">選択済み店舗（ドラッグで順序変更）</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(shop, index) in selectedShops"
                                    :key="shop.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg group"
                                    draggable="true"
                                    @dragstart="dragStart(index)"
                                    @dragover="dragOver"
                                    @drop="drop(index)">
                                    <div class="flex items-center space-x-3">
                                        <!-- ドラッグハンドル -->
                                        <div class="cursor-move text-gray-400 group-hover:text-gray-600">
                                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20">
                                                <path
                                                    d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                            </svg>
                                        </div>

                                        <!-- 順位 -->
                                        <div
                                            class="w-8 h-8 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-sm font-bold">
                                            {{ index + 1 }}
                                        </div>

                                        <!-- 店舗情報 -->
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ shop.name }}</h4>
                                            <p class="text-sm text-gray-600">{{ shop.address }}</p>
                                        </div>
                                    </div>

                                    <!-- 削除ボタン -->
                                    <button @click="removeShop(index)" type="button" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 空の状態 -->
                        <div v-else class="text-center py-6 text-gray-700">
                            <svg class="mx-auto h-12 w-12 text-gray-400 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m-2 0h2m0 0h4"></path>
                            </svg>
                            <p class="text-sm">まだ店舗が選択されていません</p>
                            <p class="text-sm">上の検索ボックスから店舗を検索して追加してください</p>
                        </div>
                    </div>

                    <!-- 送信ボタン -->
                    <div class="flex items-center justify-between pt-6">
                        <NuxtLink :to="`/rankings/${rankingId}`" class="btn-secondary">キャンセル</NuxtLink>
                        <div class="flex flex-col items-end">
                            <button
                                type="submit"
                                :disabled="!canSubmit || submitting"
                                class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                                <LoadingSpinner v-if="submitting" size="sm" color="white" class="mr-2" />
                                {{ submitting ? '更新中...' : 'ランキングを更新' }}
                            </button>
                            <p v-if="!canSubmit && !submitting" class="text-xs text-gray-700 mt-1">
                                {{
                                    !form.title.trim()
                                        ? 'ランキング名を入力してください'
                                        : selectedShops.length === 0
                                          ? '店舗を1つ以上選択してください'
                                          : 'カテゴリを選択してください'
                                }}
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Category, Ranking, Shop, RankingUpdateRequest } from '~/types/api'

// 認証ミドルウェア適用
definePageMeta({
    middleware: 'auth',
})

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()
const authStore = useAuthStore()

// リアクティブデータ
const categories = ref<Category[]>([])
const selectedShops = ref<Shop[]>([])
const shopSearchQuery = ref('')
const searchResults = ref<Shop[]>([])
const searchLoading = ref(false)
const loading = ref(true)
const error = ref('')
const submitting = ref(false)
const draggedIndex = ref<number | null>(null)

const rankingId = computed(() => parseInt(route.params.id as string))

// フォームデータ
const form = ref({
    title: '',
    description: '',
    category_id: '',
    is_public: false,
})

// バリデーション
const canSubmit = computed(() => {
    return form.value.title.trim().length > 0 && selectedShops.value.length > 0 && form.value.category_id !== ''
})

// エラー時に画面上部にスクロール
const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    })
}

// ランキング更新
const submitRanking = async () => {
    // バリデーション
    if (!form.value.title.trim()) {
        error.value = 'ランキング名を入力してください'
        scrollToTop()
        return
    }

    if (selectedShops.value.length === 0) {
        error.value = '店舗を1つ以上選択してください'
        scrollToTop()
        return
    }

    if (form.value.category_id === '') {
        error.value = 'カテゴリを選択してください'
        scrollToTop()
        return
    }

    try {
        submitting.value = true
        error.value = ''

        // 店舗データを順位付きで準備
        const shopsData = selectedShops.value.map((shop, index) => ({
            shop_id: shop.id,
            position: index + 1,
        }))

        const rankingData: RankingUpdateRequest = {
            title: form.value.title.trim(),
            description: form.value.description?.trim() || undefined,
            category_id: form.value.category_id,
            is_public: form.value.is_public,
            shops: shopsData,
        }

        await $api.rankings.update(rankingId.value, rankingData)

        // 更新成功後、詳細ページに遷移
        await router.push(`/rankings/${rankingId.value}`)
    } catch (err: unknown) {
        console.error('Failed to update ranking:', err)

        // 具体的なエラーメッセージを表示
        if (err && typeof err === 'object' && 'status' in err && 'data' in err) {
            const errorObj = err as { status: number; data?: { message?: string; messages?: Record<string, string[]> } }

            if (errorObj.status === 422 && errorObj.data?.messages) {
                // バリデーションエラーの詳細を表示
                const messages = errorObj.data.messages
                const errorMessages: string[] = []

                if (messages.title) errorMessages.push(`ランキング名: ${messages.title[0]}`)
                if (messages.description) errorMessages.push(`説明: ${messages.description[0]}`)
                if (messages.category_id) errorMessages.push(`カテゴリ: ${messages.category_id[0]}`)
                if (messages.shops) errorMessages.push(`店舗選択: ${messages.shops[0]}`)
                if (messages['shops.*.shop_id']) errorMessages.push(`店舗ID: ${messages['shops.*.shop_id'][0]}`)

                error.value =
                    errorMessages.length > 0 ? errorMessages.join('\n') : 'ランキング名を入力し、店舗を1つ以上選択してください'
            } else if (errorObj.data?.message) {
                error.value = errorObj.data.message
            } else {
                error.value = 'ランキングの更新に失敗しました'
            }
        } else {
            error.value = 'ランキングの更新に失敗しました'
        }

        // エラー時に画面上部にスクロール
        scrollToTop()
    } finally {
        submitting.value = false
    }
}

// データ読み込み
const loadData = async () => {
    try {
        loading.value = true

        // カテゴリとランキングデータを並行取得
        const [categoriesResponse, rankingResponse] = await Promise.all([
            $api.categories.list(),
            $api.rankings.get(rankingId.value),
        ])

        categories.value = categoriesResponse.data || []
        const ranking = rankingResponse.data as Ranking

        // 所有者チェック
        if (ranking.user?.id !== authStore.user?.id) {
            error.value = 'このランキングを編集する権限がありません'
            return
        }

        // フォームに既存データを設定
        form.value = {
            title: ranking.title || '',
            description: ranking.description || '',
            category_id: ranking.category?.id?.toString() || '',
            is_public: ranking.is_public || false,
        }

        // 新しい構造ではランキング詳細にshops配列が含まれている
        if (ranking.shops && ranking.shops.length > 0) {
            // 店舗データを順位順でソート
            selectedShops.value = ranking.shops.sort((a, b) => a.rank_position - b.rank_position)
        } else {
            selectedShops.value = []
        }
    } catch (err: unknown) {
        console.error('Failed to load data:', err)
        if (err && typeof err === 'object' && 'status' in err) {
            const errorObj = err as { status: number }
            if (errorObj.status === 404) {
                error.value = 'ランキングが見つかりませんでした'
            } else if (errorObj.status === 403) {
                error.value = 'このランキングを編集する権限がありません'
            } else {
                error.value = 'データの取得に失敗しました'
            }
        } else {
            error.value = 'データの取得に失敗しました'
        }
    } finally {
        loading.value = false
    }
}

// 店舗検索
const handleShopSearch = useDebounceFn(async () => {
    if (!shopSearchQuery.value || shopSearchQuery.value.length < 2) {
        searchResults.value = []
        return
    }

    try {
        searchLoading.value = true
        const response = await $api.shops.list({ search: shopSearchQuery.value })
        searchResults.value = response.data || []
    } catch (err) {
        console.error('Failed to search shops:', err)
        searchResults.value = []
    } finally {
        searchLoading.value = false
    }
}, 300)

// 店舗追加
const addShop = (shop: Shop) => {
    if (!isShopSelected(shop.id)) {
        selectedShops.value.push(shop)
        shopSearchQuery.value = ''
        searchResults.value = []
    }
}

// 店舗削除
const removeShop = (index: number) => {
    selectedShops.value.splice(index, 1)
}

// 店舗選択チェック
const isShopSelected = (shopId: number) => {
    return selectedShops.value.some((shop) => shop.id === shopId)
}

// ドラッグ&ドロップ
const dragStart = (index: number) => {
    draggedIndex.value = index
}

const dragOver = (event: DragEvent) => {
    event.preventDefault()
}

const drop = (dropIndex: number) => {
    if (draggedIndex.value === null) return

    const draggedShop = selectedShops.value[draggedIndex.value]
    selectedShops.value.splice(draggedIndex.value, 1)
    selectedShops.value.splice(dropIndex, 0, draggedShop)

    draggedIndex.value = null
}

// 初期化
onMounted(async () => {
    await loadData()
})

// メタデータ設定
useHead({
    title: 'ランキング編集 - マジキチメシ',
    meta: [{ name: 'description', content: 'ランキングを編集しましょう' }],
})
</script>
