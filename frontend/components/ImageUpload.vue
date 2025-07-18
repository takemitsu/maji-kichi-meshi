<template>
    <div class="space-y-4">
        <!-- ファイル選択 -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">画像を追加 (最大5枚)</label>
            <div class="flex items-center space-x-4">
                <input ref="fileInput" type="file" multiple accept="image/*" @change="handleFileSelect" class="hidden" />
                <button
                    type="button"
                    @click="fileInput?.click()"
                    class="btn-secondary flex items-center"
                    :disabled="images.length >= 5">
                    <svg class="w-4 h-4 mr-2 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    画像を選択
                </button>
                <span class="text-sm text-gray-700">{{ images.length }}/5</span>
            </div>
        </div>

        <!-- 画像プレビュー -->
        <div v-if="images.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div v-for="(image, index) in images" :key="index" class="relative group">
                <img :src="image.preview" :alt="`画像 ${index + 1}`" class="w-full h-32 object-cover rounded-lg" />
                <button
                    type="button"
                    @click="removeImage(index)"
                    class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <svg class="w-4 h-4 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- エラーメッセージ -->
        <div v-if="error" class="text-red-600 text-sm">
            {{ error }}
        </div>
    </div>
</template>

<script setup lang="ts">
interface ImageFile {
    file: File
    preview: string
}

interface Props {
    modelValue: File[]
    maxFiles?: number
}

interface Emits {
    (e: 'update:modelValue', files: File[]): void
}

const props = withDefaults(defineProps<Props>(), {
    maxFiles: 5,
})

const emit = defineEmits<Emits>()

const fileInput = ref<HTMLInputElement>()
const images = ref<ImageFile[]>([])
const error = ref('')

// ファイル選択ハンドラー
const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement
    const files = Array.from(target.files || [])

    error.value = ''

    // ファイル数制限チェック
    if (images.value.length + files.length > props.maxFiles) {
        error.value = `最大${props.maxFiles}枚まで選択できます`
        return
    }

    // ファイルタイプチェック
    const invalidFiles = files.filter((file) => !file.type.startsWith('image/'))
    if (invalidFiles.length > 0) {
        error.value = '画像ファイルのみ選択してください'
        return
    }

    // ファイルサイズチェック (5MB制限)
    const oversizedFiles = files.filter((file) => file.size > 5 * 1024 * 1024)
    if (oversizedFiles.length > 0) {
        error.value = 'ファイルサイズは5MB以下にしてください'
        return
    }

    // プレビュー画像を生成
    files.forEach((file) => {
        const reader = new FileReader()
        reader.onload = (e) => {
            images.value.push({
                file,
                preview: e.target?.result as string,
            })
            updateModelValue()
        }
        reader.readAsDataURL(file)
    })

    // inputをクリア
    target.value = ''
}

// 画像削除
const removeImage = (index: number) => {
    images.value.splice(index, 1)
    updateModelValue()
}

// モデル値更新
const updateModelValue = () => {
    emit(
        'update:modelValue',
        images.value.map((img) => img.file),
    )
}

// 外部からの値変更に対応
watch(
    () => props.modelValue,
    (newFiles) => {
        if (newFiles.length === 0) {
            images.value = []
        }
    },
    { deep: true },
)
</script>
