import { defineStore } from 'pinia'

interface ToastState {
    message: string
    visible: boolean
    timeoutId: ReturnType<typeof setTimeout> | null
}

export const useToastStore = defineStore('toast', {
    state: (): ToastState => ({
        message: '',
        visible: false,
        timeoutId: null,
    }),

    actions: {
        showLoginToast() {
            // 既存のタイマーをクリア
            if (this.timeoutId) {
                clearTimeout(this.timeoutId)
                this.timeoutId = null
            }

            this.message = 'ログインしてね'
            this.visible = true

            // 2秒後に非表示
            this.timeoutId = setTimeout(() => {
                this.visible = false
                this.timeoutId = null
            }, 2000)
        },

        hide() {
            if (this.timeoutId) {
                clearTimeout(this.timeoutId)
                this.timeoutId = null
            }
            this.visible = false
        },
    },
})
