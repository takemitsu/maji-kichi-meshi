<template>
    <div class="mx-auto max-w-4xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- ヘッダー -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                            新しい店舗を登録
                        </h1>
                        <p class="mt-1 text-sm text-gray-700">吉祥寺の新しいお気に入り店舗を追加しましょう</p>
                    </div>
                </div>
            </div>

            <!-- エラーメッセージ -->
            <AlertMessage v-if="error" type="error" :message="error" @close="error = ''" />

            <!-- フォーム -->
            <form @submit.prevent="submitForm" class="space-y-4 md:space-y-6">
                <div class="bg-white rounded-lg shadow p-4 md:p-6">
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
                                placeholder="東京都武蔵野市吉祥寺..."
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
                            <p class="mt-1 text-sm text-gray-700">{{ (form.description || '').length }}/1000 文字</p>
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

                <!-- 送信ボタン -->
                <div class="flex items-center justify-between pt-6">
                    <NuxtLink to="/shops" class="btn-secondary">キャンセル</NuxtLink>
                    <button
                        type="submit"
                        :disabled="!canSubmit || submitting"
                        class="btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <LoadingSpinner v-if="submitting" size="sm" color="white" class="mr-2" />
                        {{ submitting ? '登録中...' : '店舗を登録' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { Category } from '~/types/api'

// 認証ミドルウェア適用
definePageMeta({
    middleware: 'auth',
})

const router = useRouter()
const { $api } = useNuxtApp()

// リアクティブデータ
const submitting = ref(false)
const error = ref('')
const categories = ref<Category[]>([])

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

        const shopData = {
            name: form.value.name.trim(),
            address: form.value.address.trim(),
            phone: form.value.phone.trim() || undefined,
            description: form.value.description.trim() || undefined,
            category_ids: form.value.category_ids,
        }

        const response = await $api.shops.create(shopData)

        // 作成成功後、詳細ページに遷移
        await router.push(`/shops/${response.data.id}`)
    } catch (err: unknown) {
        console.error('Failed to create shop:', err)
        if (err && typeof err === 'object' && 'status' in err) {
            const errorObj = err as { status: number }
            if (errorObj.status === 422) {
                error.value = 'フォームの入力内容を確認してください'
            } else {
                error.value = '店舗の登録に失敗しました'
            }
        } else {
            error.value = '店舗の登録に失敗しました'
        }
    } finally {
        submitting.value = false
    }
}

// 初期化
onMounted(async () => {
    await loadCategories()
})

// メタデータ設定
useHead({
    title: '店舗登録 - マジキチメシ',
    meta: [{ name: 'description', content: '新しい店舗を登録します' }],
})
</script>
