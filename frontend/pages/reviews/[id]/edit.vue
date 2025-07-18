<template>
    <div class="mx-auto max-w-3xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- ローディング -->
            <LoadingSpinner v-if="loading" fullscreen />

            <!-- ヘッダー -->
            <div v-if="!loading" class="mb-8">
                <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    レビューを編集
                </h1>
                <p v-if="review" class="mt-1 text-sm text-gray-700">{{ review.shop?.name }} のレビューを編集しています</p>
            </div>

            <!-- エラーメッセージ -->
            <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

            <!-- フォーム -->
            <form v-if="!loading && review" @submit.prevent="submitReview" class="space-y-6">
                <!-- 店舗情報（読み取り専用） -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">店舗情報</h3>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h4 class="font-medium text-gray-900">{{ review.shop?.name }}</h4>
                            <p class="text-sm text-gray-600">{{ review.shop?.address }}</p>
                        </div>
                        <NuxtLink :to="`/shops/${review.shop?.id}`" class="text-sm text-blue-600 hover:text-blue-800">
                            店舗詳細
                        </NuxtLink>
                    </div>
                </div>

                <!-- 評価 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">評価</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 星評価 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                星評価
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="flex items-center space-x-1">
                                <button
                                    v-for="star in 5"
                                    :key="star"
                                    @click="form.rating = star"
                                    type="button"
                                    class="focus:outline-none">
                                    <svg
                                        class="w-8 h-8 transition-colors fill-current"
                                        :class="
                                            star <= form.rating
                                                ? 'text-yellow-400 hover:text-yellow-500'
                                                : 'text-gray-300 hover:text-gray-400'
                                        "
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </button>
                                <span class="ml-2 text-sm text-gray-600">({{ form.rating }}/5)</span>
                            </div>
                        </div>

                        <!-- リピート意向 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                リピート意向
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label v-for="option in repeatOptions" :key="option.value" class="flex items-center">
                                    <input
                                        v-model="form.repeat_intention"
                                        :value="option.value"
                                        type="radio"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                                    <span class="ml-2 text-sm text-gray-900">{{ option.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 訪問日 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">訪問日</h3>
                    <div class="max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            訪問した日付
                            <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.visited_at" type="date" class="input-field" :max="today" required />
                    </div>
                </div>

                <!-- コメント -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">コメント</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">感想・メモ（任意）</label>
                        <textarea
                            v-model="form.memo"
                            rows="6"
                            class="input-field"
                            placeholder="味の感想、雰囲気、サービスなど、自由に記録してください..."></textarea>
                        <p class="mt-2 text-sm text-gray-700">{{ (form.memo || '').length }}/1000 文字</p>
                    </div>
                </div>

                <!-- 写真管理 -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">写真</h3>

                    <!-- 既存の画像 -->
                    <div v-if="existingImages.length > 0" class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">現在の画像</h4>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div v-for="image in existingImages" :key="image.id" class="relative group">
                                <img
                                    :src="image.urls.small"
                                    :alt="`既存画像 ${image.id}`"
                                    class="w-full h-32 object-cover rounded-lg" />
                                <button
                                    type="button"
                                    @click="deleteExistingImage(image.id)"
                                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
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

                    <!-- 新しい画像の追加 -->
                    <div v-if="existingImages.length < 5">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">新しい画像を追加</h4>
                        <ImageUpload v-model="newImages" :max-files="5 - existingImages.length" />
                    </div>

                    <div v-else class="text-sm text-gray-700">
                        最大5枚の画像が登録されています。新しい画像を追加するには、既存の画像を削除してください。
                    </div>
                </div>

                <!-- 更新履歴 -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">更新履歴</h3>
                    <div class="text-sm text-gray-600 space-y-2">
                        <div>
                            <span class="font-medium">作成日:</span>
                            {{ formatDateTime(review.created_at) }}
                        </div>
                        <div v-if="review.updated_at !== review.created_at">
                            <span class="font-medium">最終更新:</span>
                            {{ formatDateTime(review.updated_at) }}
                        </div>
                    </div>
                </div>

                <!-- 送信ボタン -->
                <div class="flex items-center justify-between pt-6">
                    <div class="flex space-x-4">
                        <NuxtLink :to="`/reviews/${review.id}`" class="btn-secondary">キャンセル</NuxtLink>
                        <button
                            @click="deleteReview"
                            type="button"
                            class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                            削除
                        </button>
                    </div>
                    <button
                        type="submit"
                        :disabled="!canSubmit || submitting"
                        class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <LoadingSpinner v-if="submitting" size="sm" color="white" class="mr-2" />
                        {{ submitting ? '更新中...' : 'レビューを更新' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Review, ReviewImage } from '~/types/api'

// 認証ミドルウェア適用
definePageMeta({
    middleware: 'auth',
})

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()

// リアクティブデータ
const review = ref<Review | null>(null)
const loading = ref(true)
const error = ref('')
const submitting = ref(false)

// 画像関連
const existingImages = ref<ReviewImage[]>([])
const newImages = ref<File[]>([])

const reviewId = computed(() => parseInt(route.params.id as string))

// フォームデータ
const form = ref({
    rating: 0,
    repeat_intention: '',
    visited_at: '',
    memo: '',
})

// リピート意向オプション
const repeatOptions = [
    { value: 'yes', label: 'またいく' },
    { value: 'maybe', label: 'わからん' },
    { value: 'no', label: 'いかない' },
]

// 今日の日付（最大値として使用）
const today = new Date().toISOString().split('T')[0]

// バリデーション
const canSubmit = computed(() => {
    return form.value.rating > 0 && form.value.repeat_intention && form.value.visited_at
})

// レビューデータ取得
const loadReview = async () => {
    try {
        loading.value = true
        const response = await $api.reviews.get(reviewId.value)
        review.value = response.data

        // フォームにデータを設定
        form.value = {
            rating: review.value.rating,
            repeat_intention: review.value.repeat_intention,
            visited_at: review.value.visited_at.split('T')[0], // 日付部分のみ
            memo: review.value.memo || '',
        }

        // 既存の画像を設定
        existingImages.value = review.value.images || []
    } catch (err: unknown) {
        console.error('Failed to load review:', err)
        if (err && typeof err === 'object' && 'response' in err) {
            const errorObj = err as { response: { status: number } }
            if (errorObj.response?.status === 404) {
                error.value = 'レビューが見つかりませんでした'
            } else if (errorObj.response?.status === 403) {
                error.value = 'このレビューを編集する権限がありません'
            } else {
                error.value = 'レビューデータの取得に失敗しました'
            }
        } else {
            error.value = 'レビューデータの取得に失敗しました'
        }
    } finally {
        loading.value = false
    }
}

// レビュー更新
const submitReview = async () => {
    if (!canSubmit.value) return

    try {
        submitting.value = true

        // レビューデータを更新
        await $api.reviews.update(reviewId.value, form.value)

        // 新しい画像があればアップロード
        if (newImages.value.length > 0) {
            try {
                await $api.reviews.uploadImages(reviewId.value, newImages.value)
            } catch (imageErr) {
                console.error('Failed to upload images:', imageErr)
                error.value = 'レビューは更新されましたが、画像のアップロードに失敗しました'
            }
        }

        // 更新成功後、詳細ページに遷移
        await router.push(`/reviews/${reviewId.value}`)
    } catch (err: unknown) {
        console.error('Failed to update review:', err)
        if (err && typeof err === 'object' && 'response' in err) {
            const errorObj = err as { response: { status: number } }
            if (errorObj.response?.status === 422) {
                error.value = 'フォームの入力内容を確認してください'
            } else if (errorObj.response?.status === 403) {
                error.value = 'このレビューを編集する権限がありません'
            } else {
                error.value = 'レビューの更新に失敗しました'
            }
        } else {
            error.value = 'レビューの更新に失敗しました'
        }
    } finally {
        submitting.value = false
    }
}

// レビュー削除
const deleteReview = async () => {
    if (!review.value || !confirm(`レビュー「${review.value.shop?.name}」を削除しますか？この操作は元に戻せません。`)) {
        return
    }

    try {
        await $api.reviews.delete(reviewId.value)
        await router.push('/reviews')
    } catch (err: unknown) {
        console.error('Failed to delete review:', err)
        if (err && typeof err === 'object' && 'response' in err) {
            const errorObj = err as { response: { status: number } }
            if (errorObj.response?.status === 403) {
                error.value = 'このレビューを削除する権限がありません'
            } else {
                error.value = 'レビューの削除に失敗しました'
            }
        } else {
            error.value = 'レビューの削除に失敗しました'
        }
    }
}

// 既存画像削除
const deleteExistingImage = async (imageId: number) => {
    if (!confirm('この画像を削除しますか？')) return

    try {
        await $api.reviews.deleteImage(reviewId.value, imageId)
        existingImages.value = existingImages.value.filter((img) => img.id !== imageId)
    } catch (err) {
        console.error('Failed to delete image:', err)
        error.value = '画像の削除に失敗しました'
    }
}

// ユーティリティ関数
const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('ja-JP')
}

// 初期化
onMounted(async () => {
    await loadReview()
})

// メタデータ設定
useHead(() => ({
    title: review.value ? `${review.value.shop?.name} のレビュー編集 - マジキチメシ` : 'レビュー編集 - マジキチメシ',
    meta: [
        {
            name: 'description',
            content: review.value ? `${review.value.shop?.name}のレビューを編集` : 'レビュー編集ページ',
        },
    ],
}))
</script>
