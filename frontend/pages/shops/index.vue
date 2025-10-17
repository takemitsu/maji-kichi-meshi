<template>
    <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- ヘッダー -->
            <div class="mb-4">
                <div class="md:flex md:items-center md:justify-between">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                            店舗一覧
                        </h1>
                        <p class="mt-1 text-sm text-gray-700">
                            {{
                                authStore.isLoggedIn
                                    ? '登録済みの店舗を検索・追加・編集できます'
                                    : '登録されている店舗を検索・閲覧できます（編集にはログインが必要です）'
                            }}
                        </p>
                    </div>
                    <div v-if="authStore.isLoggedIn" class="mt-4 flex md:ml-4 md:mt-0">
                        <NuxtLink to="/shops/create" class="btn-primary flex items-center">
                            <svg class="w-4 h-4 mr-2 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            店舗を追加
                        </NuxtLink>
                    </div>
                    <div v-else class="mt-4 flex md:ml-4 md:mt-0">
                        <span class="text-sm text-gray-700">
                            <NuxtLink to="/login" class="text-blue-600 hover:text-blue-800 underline">ログイン</NuxtLink>
                            して店舗を追加
                        </span>
                    </div>
                </div>
            </div>

            <!-- 検索・フィルター -->
            <div class="mb-4 space-y-4">
                <!-- 1行目: 検索 + PC時はカテゴリ・並び順も表示 -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- 検索 -->
                    <div class="md:col-span-2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                v-model="searchQuery"
                                @input="handleSearch"
                                type="text"
                                placeholder="検索..."
                                class="w-full py-2 pr-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" />
                            <div v-if="searchLoading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                            </div>
                        </div>
                    </div>

                    <!-- カテゴリフィルター（PC表示） -->
                    <div class="hidden md:block">
                        <select v-model="selectedCategory" @change="handleCategoryFilter" class="input-field">
                            <option value="">全てのカテゴリ</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 並び順（PC表示） -->
                    <div class="hidden md:block">
                        <select v-model="selectedSort" @change="handleSortChange" class="input-field">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- 2行目: モバイル表示（カテゴリ・並び順） -->
                <div class="grid grid-cols-2 gap-4 md:hidden">
                    <div>
                        <select v-model="selectedCategory" @change="handleCategoryFilter" class="input-field">
                            <option value="">全てのカテゴリ</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <select v-model="selectedSort" @change="handleSortChange" class="input-field">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- 検索結果表示 -->
                <div v-if="searchQuery || selectedCategory || selectedSort !== 'created_at_desc'" class="text-sm text-gray-700">
                    検索結果: {{ totalItems }}件中 {{ (currentPage - 1) * perPage + 1 }}〜{{
                        Math.min(currentPage * perPage, totalItems)
                    }}件を表示
                </div>
            </div>

            <!-- ローディング -->
            <LoadingSpinner v-if="loading" />

            <!-- エラーメッセージ -->
            <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

            <!-- 店舗一覧 -->
            <div v-if="!loading && shops.length > 0" class="space-y-4 md:space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <ShopCard
                        v-for="shop in shops"
                        :key="shop.id"
                        :shop="enhanceShopForDisplay(shop)"
                        :selected-category-id="selectedCategory ? parseInt(selectedCategory) : null"
                        @edit="editShop"
                        @delete="deleteShop"
                        @category-click="handleCategoryClick" />
                </div>

                <!-- ページネーション -->
                <div v-if="totalPages > 1" class="flex justify-center">
                    <PaginationComponent
                        :current-page="currentPage"
                        :total-pages="totalPages"
                        :total-items="totalItems"
                        :per-page="perPage"
                        @page-change="handlePageChange" />
                </div>
            </div>

            <!-- 空の状態 -->
            <div v-if="!loading && shops.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H7m-2 0h2m0 0h4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">店舗がありません</h3>
                <p class="mt-1 text-sm text-gray-700">
                    {{
                        searchQuery || selectedCategory
                            ? '検索条件に一致する店舗が見つかりませんでした。'
                            : '最初の店舗を追加してみましょう。'
                    }}
                </p>
                <div class="mt-6">
                    <NuxtLink to="/shops/create" class="btn-primary inline-flex items-center">
                        <svg class="w-4 h-4 mr-2 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        店舗を追加
                    </NuxtLink>
                </div>
            </div>
        </div>

        <!-- 店舗追加/編集モーダル（今後実装） -->
        <!-- <ShopModal v-if="showAddModal" @close="showAddModal = false" /> -->
    </div>
</template>

<script setup lang="ts">
import type { Shop, Category } from '~/types/api'

// 店舗閲覧はログイン不要、操作時にログインチェック

const { $api } = useNuxtApp()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// リアクティブデータ
const shops = ref<Shop[]>([])
const categories = ref<Category[]>([])
const loading = ref(true)
const searchLoading = ref(false)
const error = ref('')
const searchQuery = ref((route.query.search as string) || '')
const selectedCategory = ref((route.query.category as string) || '')
const selectedSort = ref((route.query.sort as string) || 'created_at_desc')

// 並び順選択肢
const sortOptions = [
    { value: 'created_at_desc', label: '新しい順' },
    { value: 'review_latest', label: '最新レビュー順' },
    { value: 'reviews_count_desc', label: 'レビュー数順' },
    { value: 'rating_desc', label: '評価順' },
]

// ページネーション（URLクエリから初期値を取得）
const currentPage = ref(parseInt((route.query.page as string) || '1'))
const perPage = ref(24)
const totalItems = ref(0)
const totalPages = ref(0)

// 検索とハイライト機能
const { highlightText } = useSearchHighlight()

// 表示用の店舗データ拡張
const enhanceShopForDisplay = (shop: Shop) => {
    if (!searchQuery.value) return shop

    return {
        ...shop,
        highlightedName: highlightText(shop.name, searchQuery.value),
        highlightedAddress: highlightText(shop.address || '', searchQuery.value),
    }
}

// URLクエリパラメータを更新する関数
const updateQueryParams = (page: number) => {
    const query: Record<string, string> = {}

    // 既存のクエリパラメータを保持
    if (searchQuery.value) query.search = searchQuery.value
    if (selectedCategory.value) query.category = selectedCategory.value
    if (selectedSort.value !== 'created_at_desc') query.sort = selectedSort.value // デフォルト値は省略

    // ページ番号を追加（1ページ目の場合は省略）
    if (page > 1) {
        query.page = page.toString()
    }

    router.push({ query })
}

// 検索とフィルター
const handleSearch = useDebounceFn(() => {
    currentPage.value = 1 // 検索時は1ページ目に戻る
    updateQueryParams(1)
    loadShops()
}, 300)

const handleCategoryFilter = () => {
    currentPage.value = 1 // フィルター変更時は1ページ目に戻る
    updateQueryParams(1)
    loadShops()
}

const handleCategoryClick = (categoryId: number) => {
    // 既に選択中のカテゴリをクリックした場合はフィルタ解除
    if (selectedCategory.value === categoryId.toString()) {
        selectedCategory.value = ''
    } else {
        selectedCategory.value = categoryId.toString()
    }
    currentPage.value = 1 // カテゴリ変更時は1ページ目に戻る
    updateQueryParams(1)
    loadShops()
    // ページトップにスクロール
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleSortChange = () => {
    currentPage.value = 1 // ソート変更時は1ページ目に戻る
    updateQueryParams(1)
    loadShops()
}

// ページ変更
const handlePageChange = (page: number) => {
    currentPage.value = page
    updateQueryParams(page)
    loadShops()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

// ShopCardコンポーネントでアクションメニューを処理

// 店舗データ取得
const loadShops = async () => {
    try {
        loading.value = true

        const params: Record<string, string | number> = {
            page: currentPage.value,
            per_page: perPage.value,
        }

        if (searchQuery.value) params.search = searchQuery.value
        if (selectedCategory.value) params.category = selectedCategory.value
        if (selectedSort.value) params.sort = selectedSort.value

        const response = await $api.shops.list(params)

        // ページネーション対応のレスポンス処理
        shops.value = response.data || []

        if (response.meta) {
            currentPage.value = response.meta.current_page
            perPage.value = response.meta.per_page
            totalItems.value = response.meta.total
            totalPages.value = response.meta.last_page
        }
    } catch (err: unknown) {
        console.error('Failed to load shops:', err)
        if (err && typeof err === 'object' && 'status' in err) {
            const errorObj = err as { status: number; data?: unknown }
            error.value = `店舗データの取得に失敗しました (${errorObj.status})`
        } else {
            error.value = '店舗データの取得に失敗しました'
        }
    } finally {
        loading.value = false
    }
}

// カテゴリデータ取得
const loadCategories = async () => {
    try {
        const response = await $api.categories.list()
        categories.value = response.data || []
    } catch (err) {
        console.error('Failed to load categories:', err)
    }
}

// 店舗操作
const editShop = (shop: Shop) => {
    // 編集ページに遷移
    navigateTo(`/shops/${shop.id}/edit`)
}

const deleteShop = async (shop: Shop) => {
    if (!confirm(`店舗「${shop.name}」を削除しますか？この操作は元に戻せません。`)) {
        return
    }

    try {
        await $api.shops.delete(shop.id)
        await loadShops()
    } catch (err) {
        console.error('Failed to delete shop:', err)
        error.value = '店舗の削除に失敗しました'
    }
}

// ユーティリティ関数（今後使用予定）
// const formatDate = (dateString: string) => {
//   return new Date(dateString).toLocaleDateString('ja-JP')
// }

// ShopCardコンポーネントで外部クリック処理

// ページ番号の監視（ブラウザバック/フォワード対応）
watch(
    () => route.query.page,
    (newPage) => {
        const page = parseInt((newPage as string) || '1')
        if (currentPage.value !== page) {
            currentPage.value = page
            loadShops()
            window.scrollTo({ top: 0, behavior: 'smooth' })
        }
    },
)

// 検索・フィルタ・ソートパラメータの監視（URL直接アクセス・ブラウザバック対応）
watch([() => route.query.search, () => route.query.category, () => route.query.sort], ([newSearch, newCategory, newSort]) => {
    const search = (newSearch as string) || ''
    const category = (newCategory as string) || ''
    const sort = (newSort as string) || 'created_at_desc'

    if (searchQuery.value !== search || selectedCategory.value !== category || selectedSort.value !== sort) {
        searchQuery.value = search
        selectedCategory.value = category
        selectedSort.value = sort
        currentPage.value = 1
        loadShops()
    }
})

// 初期化
onMounted(async () => {
    await Promise.all([loadShops(), loadCategories()])
})

// メタデータ設定
useHead({
    title: '店舗一覧 - マジキチメシ',
    meta: [{ name: 'description', content: '登録済み店舗の一覧・管理ページ' }],
})
</script>
