<template>
    <div class="flex flex-col gap-2">
        <label v-if="showLabel" class="text-sm font-medium text-gray-700">行きたい度:</label>
        <div class="flex gap-2">
            <button
                v-for="option in priorityOptions"
                :key="option.value"
                type="button"
                :disabled="disabled || isLoading"
                :class="[
                    'px-3 py-1.5 rounded text-sm font-medium transition-all duration-200',
                    getPriorityButtonClass(option.value),
                    (disabled || isLoading) && 'opacity-50 cursor-not-allowed',
                ]"
                @click="handlePriorityChange(option.value)">
                {{ option.label }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
    shopId: number
    modelValue: 1 | 2 | 3
    disabled?: boolean
    showLabel?: boolean
}>()

const emit = defineEmits<{
    'update:modelValue': [value: 1 | 2 | 3]
    priorityChanged: [value: 1 | 2 | 3]
}>()

const api = useApi()
const isLoading = ref(false)

const priorityOptions = [
    { value: 1 as const, label: 'いつか', color: 'gray' },
    { value: 2 as const, label: 'そのうち', color: 'yellow' },
    { value: 3 as const, label: '絶対', color: 'red' },
]

// 優先度ボタンのクラスを取得
const getPriorityButtonClass = (value: 1 | 2 | 3) => {
    const isSelected = props.modelValue === value

    if (isSelected) {
        // 選択中: 色付き背景
        if (value === 1) {
            return 'bg-gray-500 text-white hover:bg-gray-600'
        }
        if (value === 2) {
            return 'bg-yellow-500 text-white hover:bg-yellow-600'
        }
        if (value === 3) {
            return 'bg-red-500 text-white hover:bg-red-600'
        }
    }

    // 未選択: グレー背景
    return 'bg-gray-200 text-gray-600 hover:bg-gray-300'
}

// 優先度変更
const handlePriorityChange = async (value: 1 | 2 | 3) => {
    if (props.disabled || isLoading.value || props.modelValue === value) {
        return
    }

    isLoading.value = true

    try {
        await api.wishlists.updatePriority(props.shopId, { priority: value })
        emit('update:modelValue', value)
        emit('priorityChanged', value)
    } catch (error) {
        console.error('Failed to update priority:', error)
        alert('優先度の変更に失敗しました')
    } finally {
        isLoading.value = false
    }
}
</script>
