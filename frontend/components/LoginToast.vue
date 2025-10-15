<template>
    <Transition name="toast">
        <div
            v-if="toastStore.visible"
            class="fixed bottom-1/4 md:bottom-1/3 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-lg text-sm font-medium shadow-lg pointer-events-none border border-blue-200 bg-blue-50 text-blue-800">
            {{ toastStore.message }}
        </div>
    </Transition>
</template>

<script setup lang="ts">
const toastStore = useToastStore()
</script>

<style scoped>
.toast-enter-active {
    transition: opacity 0.2s ease-out, transform 0.2s ease-out;
}

.toast-leave-active {
    transition: opacity 0.3s ease-in, transform 0.3s ease-in;
}

.toast-enter-from {
    opacity: 0;
    transform: translate(-50%, 1rem);
}

.toast-leave-to {
    opacity: 0;
    transform: translate(-50%, 0.5rem);
}
</style>
