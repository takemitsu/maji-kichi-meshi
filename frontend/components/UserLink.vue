<template>
  <NuxtLink 
    :to="getUserPageUrl()" 
    class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200"
    :class="customClass"
    @click="trackUserClick"
  >
    {{ user.name }}
  </NuxtLink>
</template>

<script setup lang="ts">
// import type { User } from '~/types/api'

interface Props {
  user: {
    id: number
    name: string
  }
  pageType: 'reviews' | 'rankings'
  customClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  customClass: '',
})

const getUserPageUrl = () => {
  if (props.pageType === 'rankings') {
    return `/rankings/public?user_id=${props.user.id}`
  }
  return `/${props.pageType}?user_id=${props.user.id}`
}

// アナリティクス用（将来的に追加可能）
const trackUserClick = () => {
  // console.log(`User ${props.user.id} clicked for ${props.pageType}`)
}
</script>