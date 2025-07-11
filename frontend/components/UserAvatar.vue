<template>
    <div class="relative inline-block">
        <!-- プロフィール画像がある場合 -->
        <img
            v-if="profileImageUrl"
            :src="profileImageUrl"
            :alt="`${userName}のプロフィール画像`"
            :class="avatarClasses"
            class="object-cover"
            @error="handleImageError"
        />
        
        <!-- プロフィール画像がない場合（デフォルトアバター） -->
        <div
            v-else
            :class="avatarClasses"
            class="bg-gray-300 flex items-center justify-center text-gray-600 font-medium"
        >
            {{ initials }}
        </div>
        
        <!-- オンライン状態インジケーター（オプション） -->
        <span
            v-if="showOnline"
            class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white"
        ></span>
    </div>
</template>

<script setup lang="ts">
interface Props {
    userName: string
    profileImageUrl?: string | null
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    showOnline?: boolean
    rounded?: 'full' | 'lg' | 'md' | 'sm' | 'none'
}

const props = withDefaults(defineProps<Props>(), {
    profileImageUrl: null,
    size: 'md',
    showOnline: false,
    rounded: 'full',
})

// サイズに応じたクラス
const sizeClasses = {
    xs: 'h-6 w-6 text-xs',
    sm: 'h-8 w-8 text-sm',
    md: 'h-10 w-10 text-base',
    lg: 'h-12 w-12 text-lg',
    xl: 'h-16 w-16 text-xl',
    '2xl': 'h-20 w-20 text-2xl',
}

// 角丸に応じたクラス
const roundedClasses = {
    full: 'rounded-full',
    lg: 'rounded-lg',
    md: 'rounded-md',
    sm: 'rounded-sm',
    none: 'rounded-none',
}

const avatarClasses = computed(() => [
    sizeClasses[props.size],
    roundedClasses[props.rounded],
])

// ユーザー名のイニシャル
const initials = computed(() => {
    if (!props.userName) return '?'
    
    const names = props.userName.split(' ')
    if (names.length >= 2) {
        return names[0][0] + names[1][0]
    }
    return names[0][0] || '?'
})

// 画像読み込みエラー時の処理
const handleImageError = () => {
    // 画像が読み込めない場合はデフォルトアバターを表示
    // この場合、v-ifの条件がfalseになるようにprofileImageUrlを無効化
}
</script>

<style scoped>
/* 追加のスタイルが必要な場合 */
</style>