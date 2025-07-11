<template>
    <div class="space-y-4">
        <!-- 現在のプロフィール画像 -->
        <div class="flex items-center space-x-4">
            <UserAvatar
                :user-name="userName"
                :profile-image-url="currentImageUrl"
                size="xl"
                class="flex-shrink-0"
            />
            
            <div class="flex-1">
                <h3 class="text-lg font-medium text-gray-900">プロフィール画像</h3>
                <p class="text-sm text-gray-500">
                    JPG、PNG、GIF、WebP形式に対応。最大5MB。
                </p>
            </div>
        </div>

        <!-- アップロード・削除ボタン -->
        <div class="flex items-center space-x-3">
            <!-- ファイル選択ボタン -->
            <label
                for="profile-image-upload"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ uploading ? 'アップロード中...' : '画像を選択' }}
            </label>

            <!-- 隠しファイル入力 -->
            <input
                id="profile-image-upload"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleFileSelect"
                :disabled="uploading"
            />

            <!-- 削除ボタン -->
            <button
                v-if="hasCurrentImage"
                @click="deleteImage"
                :disabled="deleting"
                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ deleting ? '削除中...' : '画像を削除' }}
            </button>
        </div>

        <!-- プログレスバー -->
        <div v-if="uploading || deleting" class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out" :style="{ width: '100%' }"></div>
        </div>

        <!-- エラーメッセージ -->
        <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-3">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p class="text-sm text-red-800">{{ error }}</p>
            </div>
        </div>

        <!-- 成功メッセージ -->
        <div v-if="success" class="bg-green-50 border border-green-200 rounded-md p-3">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm text-green-800">{{ success }}</p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    userName: string
    currentImageUrl?: string | null
}

interface Emits {
    (event: 'uploaded', imageUrls: Record<string, string>): void
    (event: 'deleted'): void
}

const props = withDefaults(defineProps<Props>(), {
    currentImageUrl: null,
})

const emit = defineEmits<Emits>()

const { $api } = useNuxtApp()

// 状態管理
const uploading = ref(false)
const deleting = ref(false)
const error = ref('')
const success = ref('')

// 現在画像があるかどうか
const hasCurrentImage = computed(() => !!props.currentImageUrl)

// 現在の画像URLを監視
const currentImageUrl = ref(props.currentImageUrl)

// プロパティの変更を監視
watch(
    () => props.currentImageUrl,
    (newUrl) => {
        currentImageUrl.value = newUrl
    },
    { immediate: true },
)

// ファイル選択時の処理
const handleFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    
    if (!file) return

    // バリデーション
    if (!(await validateFile(file))) {
        return
    }

    await uploadImage(file)
    
    // ファイル入力をクリア
    target.value = ''
}

// ファイルバリデーション
const validateFile = async (file: File): Promise<boolean> => {
    error.value = ''
    
    // ファイル形式チェック
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
    if (!allowedTypes.includes(file.type)) {
        error.value = 'JPG、PNG、GIF、WebP形式のファイルのみアップロード可能です。'
        return false
    }
    
    // ファイルサイズチェック（5MB）
    const maxSize = 5 * 1024 * 1024
    if (file.size > maxSize) {
        error.value = 'ファイルサイズは5MB以下にしてください。'
        return false
    }
    
    // 画像の最小サイズチェック
    return await new Promise<boolean>((resolve) => {
        const img = new Image()
        img.onload = () => {
            if (img.width < 100 || img.height < 100) {
                error.value = '画像は100x100ピクセル以上にしてください。'
                resolve(false)
            } else {
                resolve(true)
            }
        }
        img.onerror = () => {
            error.value = '画像ファイルが不正です。'
            resolve(false)
        }
        img.src = URL.createObjectURL(file)
    })
}

// 画像アップロード
const uploadImage = async (file: File) => {
    uploading.value = true
    error.value = ''
    success.value = ''
    
    try {
        const response = await $api.profile.uploadImage(file)
        
        success.value = 'プロフィール画像をアップロードしました。'
        
        // 現在の画像URLを更新
        currentImageUrl.value = response.data.profile_image.urls.medium || response.data.profile_image.urls.large || null
        
        emit('uploaded', response.data.profile_image.urls)
        
        // 成功メッセージを3秒後に消す
        setTimeout(() => {
            success.value = ''
        }, 3000)
        
    } catch (err: unknown) {
        console.error('Profile image upload failed:', err)
        
        const errorWithData = err as { status?: number; data?: { messages?: Record<string, string[]> } }
        if (errorWithData.status === 422 && errorWithData.data?.messages) {
            // バリデーションエラー
            const messages = errorWithData.data.messages
            if (messages.profile_image) {
                error.value = messages.profile_image[0]
            } else {
                error.value = 'アップロードに失敗しました。入力内容を確認してください。'
            }
        } else {
            error.value = 'アップロードに失敗しました。しばらく時間をおいて再度お試しください。'
        }
    } finally {
        uploading.value = false
    }
}

// 画像削除
const deleteImage = async () => {
    if (!confirm('プロフィール画像を削除しますか？')) {
        return
    }
    
    deleting.value = true
    error.value = ''
    success.value = ''
    
    try {
        await $api.profile.deleteImage()
        
        success.value = 'プロフィール画像を削除しました。'
        
        // 現在の画像URLをクリア
        currentImageUrl.value = null
        
        emit('deleted')
        
        // 成功メッセージを3秒後に消す
        setTimeout(() => {
            success.value = ''
        }, 3000)
        
    } catch (err: unknown) {
        console.error('Profile image deletion failed:', err)
        error.value = '削除に失敗しました。しばらく時間をおいて再度お試しください。'
    } finally {
        deleting.value = false
    }
}

// メッセージをクリア
const clearMessages = () => {
    error.value = ''
    success.value = ''
}

// コンポーネントマウント時にメッセージクリア
onMounted(() => {
    clearMessages()
})
</script>

<style scoped>
/* 追加のスタイルが必要な場合 */
</style>