<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform -translate-y-2 opacity-0"
    enter-to-class="transform translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="transform translate-y-0 opacity-100"
    leave-to-class="transform -translate-y-2 opacity-0"
  >
    <div 
      v-if="show"
      :class="alertClasses"
      class="rounded-md p-4 mb-4"
    >
      <div class="flex">
        <!-- アイコン -->
        <div class="flex-shrink-0">
          <component :is="iconComponent" :class="iconClasses" class="h-5 w-5" />
        </div>
        
        <!-- メッセージ -->
        <div class="ml-3 flex-1">
          <h3 v-if="title" :class="titleClasses" class="text-sm font-medium">
            {{ title }}
          </h3>
          <div :class="messageClasses" class="text-sm">
            <slot>{{ message }}</slot>
          </div>
        </div>

        <!-- 閉じるボタン -->
        <div v-if="closable" class="ml-auto pl-3">
          <button
            @click="close"
            :class="closeButtonClasses"
            class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
          >
            <span class="sr-only">閉じる</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { h } from 'vue'

interface Props {
  type?: 'success' | 'error' | 'warning' | 'info'
  title?: string
  message?: string
  closable?: boolean
  autoClose?: boolean
  autoCloseDelay?: number
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  closable: true,
  autoClose: false,
  autoCloseDelay: 3000
})

const emit = defineEmits<{
  close: []
}>()

const show = ref(true)

// アラートのスタイルクラス
const typeStyles = {
  success: {
    alert: 'bg-green-50 border border-green-200',
    icon: 'text-green-400',
    title: 'text-green-800',
    message: 'text-green-700',
    closeButton: 'text-green-500 hover:bg-green-100 focus:ring-green-600'
  },
  error: {
    alert: 'bg-red-50 border border-red-200',
    icon: 'text-red-400',
    title: 'text-red-800',
    message: 'text-red-700',
    closeButton: 'text-red-500 hover:bg-red-100 focus:ring-red-600'
  },
  warning: {
    alert: 'bg-yellow-50 border border-yellow-200',
    icon: 'text-yellow-400',
    title: 'text-yellow-800',
    message: 'text-yellow-700',
    closeButton: 'text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600'
  },
  info: {
    alert: 'bg-blue-50 border border-blue-200',
    icon: 'text-blue-400',
    title: 'text-blue-800',
    message: 'text-blue-700',
    closeButton: 'text-blue-500 hover:bg-blue-100 focus:ring-blue-600'
  }
}

// アイコンコンポーネント
const iconComponents = {
  success: () => h('svg', {
    fill: 'currentColor',
    viewBox: '0 0 20 20'
  }, [
    h('path', {
      'fill-rule': 'evenodd',
      d: 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z',
      'clip-rule': 'evenodd'
    })
  ]),
  error: () => h('svg', {
    fill: 'currentColor',
    viewBox: '0 0 20 20'
  }, [
    h('path', {
      'fill-rule': 'evenodd',
      d: 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z',
      'clip-rule': 'evenodd'
    })
  ]),
  warning: () => h('svg', {
    fill: 'currentColor',
    viewBox: '0 0 20 20'
  }, [
    h('path', {
      'fill-rule': 'evenodd',
      d: 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
      'clip-rule': 'evenodd'
    })
  ]),
  info: () => h('svg', {
    fill: 'currentColor',
    viewBox: '0 0 20 20'
  }, [
    h('path', {
      'fill-rule': 'evenodd',
      d: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z',
      'clip-rule': 'evenodd'
    })
  ])
}

// 計算されたクラス
const alertClasses = computed(() => typeStyles[props.type].alert)
const iconClasses = computed(() => typeStyles[props.type].icon)
const titleClasses = computed(() => props.title ? typeStyles[props.type].title : '')
const messageClasses = computed(() => typeStyles[props.type].message)
const closeButtonClasses = computed(() => typeStyles[props.type].closeButton)
const iconComponent = computed(() => iconComponents[props.type])

// 閉じる処理
const close = () => {
  show.value = false
  emit('close')
}

// 自動閉じる
if (props.autoClose) {
  setTimeout(() => {
    close()
  }, props.autoCloseDelay)
}
</script>