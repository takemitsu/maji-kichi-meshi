<template>
    <div class="flex items-center justify-center" :class="containerClass">
        <div class="animate-spin rounded-full border-b-2" :class="spinnerClass"></div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    size?: 'sm' | 'md' | 'lg'
    color?: 'blue' | 'gray' | 'white'
    fullscreen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    color: 'blue',
    fullscreen: false,
})

// サイズクラス
const sizeClasses = {
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
}

// カラークラス
const colorClasses = {
    blue: 'border-blue-600',
    gray: 'border-gray-600',
    white: 'border-white',
}

// コンテナクラス
const containerClass = computed(() => ({
    'fixed inset-0 bg-gray-50 bg-opacity-75 z-50': props.fullscreen,
    'py-8': !props.fullscreen,
}))

// スピナークラス
const spinnerClass = computed(() => [sizeClasses[props.size], colorClasses[props.color]])
</script>
