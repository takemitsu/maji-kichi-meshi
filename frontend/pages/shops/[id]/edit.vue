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
                            <NuxtLink to="/shops" class="text-gray-500 hover:text-gray-700">店舗一覧</NuxtLink>
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
                            <NuxtLink v-if="form.name" :to="`/shops/${shopId}`" class="text-gray-500 hover:text-gray-700">
                                {{ form.name }}
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
                        店舗情報を編集
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">店舗の基本情報を更新します</p>
                </div>

                <!-- フォーム -->
                <form @submit.prevent="submitForm" class="space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">基本情報</h3>

                        <div class="space-y-4">
                            <!-- 店舗名 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    店舗名
                                    <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="input-field"
                                    placeholder="店舗名を入力してください"
                                    maxlength="100"
                                    required />
                            </div>

                            <!-- 住所 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">住所</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="input-field"
                                    placeholder="住所を入力してください"
                                    maxlength="255" />
                            </div>

                            <!-- 電話番号 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">電話番号</label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    class="input-field"
                                    placeholder="03-1234-5678"
                                    maxlength="20" />
                            </div>

                            <!-- 説明 -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">説明</label>
                                <textarea
                                    v-model="form.description"
                                    rows="4"
                                    class="input-field"
                                    placeholder="店舗の特徴や情報を入力してください..."
                                    maxlength="1000"></textarea>
                                <p class="mt-1 text-sm text-gray-500">{{ (form.description || '').length }}/1000 文字</p>
                            </div>

                            <!-- カテゴリ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">カテゴリ</label>
                                <div class="space-y-2">
                                    <label v-for="category in categories" :key="category.id" class="flex items-center">
                                        <input
                                            v-model="form.category_ids"
                                            :value="category.id"
                                            type="checkbox"
                                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" />
                                        <span class="ml-2 text-sm text-gray-900">{{ category.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 画像アップロード -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">店舗画像</h3>

                        <!-- 既存画像 -->
                        <div v-if="shop?.images && shop.images.length > 0" class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">現在の画像</h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div v-for="image in shop.images" :key="image.id" class="relative group">
                                    <img :src="image.urls.medium" :alt="shop.name" class="w-full h-24 object-cover rounded-lg" />
                                    <button
                                        @click="deleteImage(image.id)"
                                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-4 h-4 fill-none" stroke="currentColor" viewBox="0 0 24 24">
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

                        <!-- 画像アップロード -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">新しい画像を追加</label>
                            <ImageUpload v-model="uploadedImages" :max-files="5" />
                        </div>
                    </div>

                    <!-- 送信ボタン -->
                    <div class="flex items-center justify-between pt-6">
                        <NuxtLink :to="`/shops/${shopId}`" class="btn-secondary">キャンセル</NuxtLink>
                        <button
                            type="submit"
                            :disabled="!canSubmit || submitting"
                            class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            <LoadingSpinner v-if="submitting" size="sm" color="white" class="mr-2" />
                            {{ submitting ? '更新中...' : '更新する' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Shop, Category } from '~/types/api'

// 認証ミドルウェア適用
definePageMeta({
    middleware: 'auth',
})

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()

const shopId = computed(() => Number(route.params.id))

// リアクティブデータ
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const categories = ref<Category[]>([])
const shop = ref<Shop | null>(null)
const uploadedImages = ref<File[]>([])

// フォームデータ
const form = ref({
    name: '',
    address: '',
    phone: '',
    description: '',
    category_ids: [] as number[],
})

// バリデーション
const canSubmit = computed(() => {
    return form.value.name.trim().length > 0
})

// 店舗データ取得
const loadShop = async () => {
    try {
        const response = await $api.shops.get(shopId.value)
        shop.value = response.data

        // フォームに既存データを設定
        form.value = {
            name: shop.value.name,
            address: shop.value.address,
            phone: shop.value.phone || '',
            description: shop.value.description || '',
            category_ids: shop.value.categories?.map((cat) => cat.id) || [],
        }
    } catch (err: unknown) {
        console.error('Failed to load shop:', err)
        if (err && typeof err === 'object' && 'status' in err) {
            const errorObj = err as { status: number }
            if (errorObj.status === 404) {
                error.value = '店舗が見つかりませんでした'
            } else {
                error.value = '店舗データの取得に失敗しました'
            }
        } else {
            error.value = '店舗データの取得に失敗しました'
        }
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

// フォーム送信
const submitForm = async () => {
    if (!canSubmit.value) return

    try {
        submitting.value = true

        const updateData = {
            name: form.value.name?.trim() || '',
            address: form.value.address?.trim() || '',
            phone: form.value.phone?.trim() || undefined,
            description: form.value.description?.trim() || undefined,
            category_ids: form.value.category_ids || [],
        }

        await $api.shops.update(shopId.value, updateData)

        // 新しい画像があればアップロード
        if (uploadedImages.value.length > 0) {
            try {
                const formData = new FormData()
                uploadedImages.value.forEach((file) => {
                    formData.append('images[]', file)
                })
                await $api.shops.uploadImages(shopId.value, formData)
            } catch (imageErr) {
                console.error('Failed to upload images:', imageErr)
                error.value = '店舗は更新されましたが、画像のアップロードに失敗しました'
            }
        }

        // 更新成功後、詳細ページに遷移
        await router.push(`/shops/${shopId.value}`)
    } catch (err: unknown) {
        console.error('Failed to update shop:', err)
        if (err && typeof err === 'object' && 'status' in err) {
            const errorObj = err as { status: number }
            if (errorObj.status === 422) {
                error.value = 'フォームの入力内容を確認してください'
            } else if (errorObj.status === 403) {
                error.value = 'この店舗を編集する権限がありません'
            } else {
                error.value = '店舗の更新に失敗しました'
            }
        } else {
            error.value = '店舗の更新に失敗しました'
        }
    } finally {
        submitting.value = false
    }
}

// 画像削除
const deleteImage = async (imageId: number) => {
    if (!confirm('この画像を削除しますか？')) return

    try {
        await $api.shops.deleteImage(shopId.value, imageId)
        await loadShop() // 再読み込み
    } catch (err: unknown) {
        console.error('Failed to delete image:', err)
        error.value = '画像の削除に失敗しました'
    }
}

// 初期化
onMounted(async () => {
    try {
        await Promise.all([loadShop(), loadCategories()])
    } finally {
        loading.value = false
    }
})

// メタデータ設定
useHead({
    title: '店舗編集 - マジキチメシ',
    meta: [{ name: 'description', content: '店舗情報を編集します' }],
})
</script>
