<template>
    <div class="mx-auto max-w-2xl py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-bold text-gray-900 mb-8">アカウント設定</h1>

            <!-- 表示名変更セクション -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">表示名</h2>
                <form @submit.prevent="updateDisplayName">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">表示名</label>
                        <input
                            v-model="displayName"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="新しい表示名を入力" />
                    </div>
                    <button type="submit" class="btn-primary">保存</button>
                </form>
            </div>

            <!-- プロフィール画像セクション -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">プロフィール画像</h2>
                <ProfileImageUpload
                    :user-name="authStore.user?.name || ''"
                    :current-image-url="currentProfileImageUrl"
                    @uploaded="handleImageUploaded"
                    @deleted="handleImageDeleted"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
// 認証ミドルウェア適用
definePageMeta({
    middleware: 'auth',
})

const authStore = useAuthStore()
const { $api } = useNuxtApp()

const displayName = ref(authStore.user?.name || '')
const currentProfileImageUrl = ref<string | null>(null)

// プロフィール画像URLを取得
const fetchProfileImageUrl = async () => {
    try {
        const profile = await $api.profile.get()
        if (profile.data.profile_image?.urls?.medium) {
            currentProfileImageUrl.value = profile.data.profile_image.urls.medium
        }
    } catch (error) {
        console.error('プロフィール画像取得エラー:', error)
    }
}

// 表示名更新
const updateDisplayName = async () => {
    try {
        const response = await $api.profile.update({ name: displayName.value })

        // 認証ストアのユーザー情報を更新
        authStore.updateUser({ name: displayName.value })

        // 成功メッセージを表示（ログ）
        console.info('表示名を更新しました:', response.data.name)
    } catch (error) {
        console.error('表示名更新エラー:', error)
        // エラー時は元の値に戻す
        displayName.value = authStore.user?.name || ''
    }
}

// プロフィール画像アップロード時の処理
const handleImageUploaded = (imageUrls: Record<string, string>) => {
    currentProfileImageUrl.value = imageUrls.medium || imageUrls.large || null
}

// プロフィール画像削除時の処理
const handleImageDeleted = () => {
    currentProfileImageUrl.value = null
}

// 初期データ取得
onMounted(() => {
    fetchProfileImageUrl()
})

// メタデータ設定
useHead({
    title: 'アカウント設定 - マジキチメシ',
    meta: [{ name: 'description', content: 'アカウント設定ページ' }],
})
</script>
