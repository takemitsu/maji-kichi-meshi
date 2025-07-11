<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-2 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform -translate-y-2 opacity-0">
        <div v-if="show" :class="alertClasses" class="rounded-md p-4 mb-4">
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
                        <slot>
                            <div v-if="message" class="whitespace-pre-line">{{ message }}</div>
                        </slot>
                    </div>

                    <!-- リトライボタン -->
                    <div v-if="retryable" class="mt-3">
                        <button
                            @click="retry"
                            :disabled="retrying"
                            class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                            :class="retryButtonClasses">
                            <svg v-if="retrying" class="animate-spin -ml-1 mr-2 h-3 w-3 fill-none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path
                                    class="opacity-75 fill-current"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="w-3 h-3 mr-1 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ retrying ? '再試行中...' : '再試行' }}
                        </button>
                    </div>
                </div>

                <!-- 閉じるボタン -->
                <div v-if="closable" class="ml-auto pl-3">
                    <button
                        @click="close"
                        :class="closeButtonClasses"
                        class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2">
                        <span class="sr-only">閉じる</span>
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 20 20">
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
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
    retryable?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    closable: true,
    autoClose: false,
    autoCloseDelay: 3000,
    retryable: false,
})

const emit = defineEmits<{
    close: []
    retry: []
}>()

const show = ref(true)
const retrying = ref(false)

// アラートのスタイルクラス
const typeStyles = {
    success: {
        alert: 'bg-green-50 border border-green-200',
        icon: 'text-green-400',
        title: 'text-green-800',
        message: 'text-green-700',
        closeButton: 'text-green-500 hover:bg-green-100 focus:ring-green-600',
        retryButton: 'text-green-800 bg-green-100 hover:bg-green-200 focus:ring-green-600',
    },
    error: {
        alert: 'bg-red-50 border border-red-200',
        icon: 'text-red-400',
        title: 'text-red-800',
        message: 'text-red-700',
        closeButton: 'text-red-500 hover:bg-red-100 focus:ring-red-600',
        retryButton: 'text-red-800 bg-red-100 hover:bg-red-200 focus:ring-red-600',
    },
    warning: {
        alert: 'bg-yellow-50 border border-yellow-200',
        icon: 'text-yellow-400',
        title: 'text-yellow-800',
        message: 'text-yellow-700',
        closeButton: 'text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600',
        retryButton: 'text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:ring-yellow-600',
    },
    info: {
        alert: 'bg-blue-50 border border-blue-200',
        icon: 'text-blue-400',
        title: 'text-blue-800',
        message: 'text-blue-700',
        closeButton: 'text-blue-500 hover:bg-blue-100 focus:ring-blue-600',
        retryButton: 'text-blue-800 bg-blue-100 hover:bg-blue-200 focus:ring-blue-600',
    },
}

// アイコンコンポーネント
const iconComponents = {
    success: () =>
        h(
            'svg',
            {
                fill: 'currentColor',
                viewBox: '0 0 20 20',
            },
            [
                h('path', {
                    'fill-rule': 'evenodd',
                    d: 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z',
                    'clip-rule': 'evenodd',
                }),
            ],
        ),
    error: () =>
        h(
            'svg',
            {
                fill: 'currentColor',
                viewBox: '0 0 20 20',
            },
            [
                h('path', {
                    'fill-rule': 'evenodd',
                    d: 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z',
                    'clip-rule': 'evenodd',
                }),
            ],
        ),
    warning: () =>
        h(
            'svg',
            {
                fill: 'currentColor',
                viewBox: '0 0 20 20',
            },
            [
                h('path', {
                    'fill-rule': 'evenodd',
                    d: 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
                    'clip-rule': 'evenodd',
                }),
            ],
        ),
    info: () =>
        h(
            'svg',
            {
                fill: 'currentColor',
                viewBox: '0 0 20 20',
            },
            [
                h('path', {
                    'fill-rule': 'evenodd',
                    d: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z',
                    'clip-rule': 'evenodd',
                }),
            ],
        ),
}

// 計算されたクラス
const alertClasses = computed(() => typeStyles[props.type].alert)
const iconClasses = computed(() => typeStyles[props.type].icon)
const titleClasses = computed(() => (props.title ? typeStyles[props.type].title : ''))
const messageClasses = computed(() => typeStyles[props.type].message)
const closeButtonClasses = computed(() => typeStyles[props.type].closeButton)
const retryButtonClasses = computed(() => typeStyles[props.type].retryButton)
const iconComponent = computed(() => iconComponents[props.type])

// 閉じる処理
const close = () => {
    show.value = false
    emit('close')
}

// リトライ処理
const retry = async () => {
    retrying.value = true
    emit('retry')
    // 親コンポーネントでリトライ処理が完了した後、retrying.value を false にする責任は親にある
}

// 自動閉じる
if (props.autoClose) {
    setTimeout(() => {
        close()
    }, props.autoCloseDelay)
}
</script>
