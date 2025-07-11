<template>
    <header class="bg-white shadow-sm border-b border-gray-200">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- ロゴ・タイトル -->
                <div class="flex items-center">
                    <NuxtLink to="/" class="text-xl font-bold text-gray-900 hover:text-blue-600 transition-colors">
                        マジキチメシ
                    </NuxtLink>
                </div>

                <!-- メインナビゲーション -->
                <div class="hidden md:flex items-center space-x-8">
                    <NuxtLink
                        to="/rankings/public"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="{
                            'text-blue-600 bg-blue-50': $route.path.startsWith('/rankings'),
                        }">
                        ランキング
                    </NuxtLink>
                    <NuxtLink
                        to="/reviews"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="{
                            'text-blue-600 bg-blue-50': $route.path.startsWith('/reviews'),
                        }">
                        レビュー
                    </NuxtLink>
                    <NuxtLink
                        to="/shops"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="{
                            'text-blue-600 bg-blue-50': $route.path.startsWith('/shops'),
                        }">
                        店舗
                    </NuxtLink>
                </div>

                <!-- 認証済みユーザー専用ナビ -->
                <div v-if="authStore.isLoggedIn" class="hidden md:flex items-center">
                    <NuxtLink
                        to="/dashboard"
                        class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="{
                            'text-blue-600 bg-blue-50': $route.path === '/dashboard',
                        }">
                        マイページ
                    </NuxtLink>
                </div>

                <!-- 右側メニュー -->
                <div class="flex items-center space-x-4">
                    <!-- 未認証の場合 -->
                    <div v-if="!authStore.isLoggedIn" class="flex items-center space-x-2">
                        <NuxtLink to="/login" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            ログイン
                        </NuxtLink>
                    </div>

                    <!-- 認証済みの場合 -->
                    <div v-else class="flex items-center space-x-4">
                        <!-- ユーザーメニュー -->
                        <div class="relative" ref="userMenuRef">
                            <button
                                @click="toggleUserMenu"
                                class="flex items-center space-x-2 text-sm text-gray-600 hover:text-gray-900 focus:outline-none">
                                <UserAvatar
                                    :user-name="authStore.user?.name || 'ユーザー'"
                                    :profile-image-url="userProfileImageUrl"
                                    size="sm" />
                                <span class="hidden md:block">{{ authStore.user?.name }}</span>
                                <svg
                                    class="w-4 h-4 transition-transform fill-none"
                                    :class="{ 'rotate-180': isUserMenuOpen }"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <!-- ドロップダウンメニュー -->
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="transform scale-95 opacity-0"
                                enter-to-class="transform scale-100 opacity-100"
                                leave-active-class="transition duration-75 ease-in"
                                leave-from-class="transform scale-100 opacity-100"
                                leave-to-class="transform scale-95 opacity-0">
                                <div
                                    v-if="isUserMenuOpen"
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                                    <NuxtLink
                                        to="/settings"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        設定
                                    </NuxtLink>
                                    <button
                                        @click="logout"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                        ログアウト
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>

                    <!-- モバイルメニューボタン（常に表示） -->
                    <button
                        @click="toggleMobileMenu"
                        class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none">
                        <svg class="w-6 h-6 fill-none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                v-if="!isMobileMenuOpen"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path
                                v-else
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- モバイルメニュー -->
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0">
                <div v-if="isMobileMenuOpen" class="md:hidden py-4 border-t border-gray-200">
                    <div class="space-y-1">
                        <NuxtLink
                            to="/rankings/public"
                            class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                            :class="{
                                'text-blue-600 bg-blue-50': $route.path.startsWith('/rankings'),
                            }"
                            @click="closeMobileMenu">
                            ランキング
                        </NuxtLink>
                        <NuxtLink
                            to="/reviews"
                            class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                            :class="{
                                'text-blue-600 bg-blue-50': $route.path.startsWith('/reviews'),
                            }"
                            @click="closeMobileMenu">
                            レビュー
                        </NuxtLink>
                        <NuxtLink
                            to="/shops"
                            class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                            :class="{
                                'text-blue-600 bg-blue-50': $route.path.startsWith('/shops'),
                            }"
                            @click="closeMobileMenu">
                            店舗
                        </NuxtLink>
                        <template v-if="authStore.isLoggedIn">
                            <NuxtLink
                                to="/dashboard"
                                class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                                :class="{
                                    'text-blue-600 bg-blue-50': $route.path === '/dashboard',
                                }"
                                @click="closeMobileMenu">
                                ダッシュボード
                            </NuxtLink>
                            <NuxtLink
                                to="/reviews"
                                class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                                :class="{
                                    'text-blue-600 bg-blue-50': $route.path.startsWith('/reviews'),
                                }"
                                @click="closeMobileMenu">
                                レビュー
                            </NuxtLink>
                            <NuxtLink
                                to="/rankings"
                                class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50"
                                :class="{
                                    'text-blue-600 bg-blue-50': $route.path.startsWith('/rankings'),
                                }"
                                @click="closeMobileMenu">
                                ランキング
                            </NuxtLink>

                            <!-- モバイル用ユーザーメニュー -->
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <div class="flex items-center px-3 py-2">
                                    <UserAvatar
                                        :user-name="authStore.user?.name || 'ユーザー'"
                                        :profile-image-url="userProfileImageUrl"
                                        size="sm"
                                        class="mr-3" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ authStore.user?.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate">
                                            {{ authStore.user?.email }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-2 space-y-1">
                                    <!-- プロフィール・設定は今後実装予定 -->
                                    <button
                                        @click="logout"
                                        class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-red-600 hover:text-red-900 hover:bg-red-50">
                                        ログアウト
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- 未認証時のモバイルメニュー -->
                        <template v-else>
                            <div class="border-t border-gray-200 pt-4 mt-4">
                                <NuxtLink
                                    to="/login"
                                    class="block px-3 py-2 rounded-md text-base font-medium text-blue-600 hover:text-blue-900 hover:bg-blue-50"
                                    @click="closeMobileMenu">
                                    ログイン
                                </NuxtLink>
                            </div>
                        </template>
                    </div>
                </div>
            </Transition>
        </nav>
    </header>
</template>

<script setup lang="ts">
const authStore = useAuthStore()
const router = useRouter()
const { $api } = useNuxtApp()

// メニュー状態管理
const isUserMenuOpen = ref(false)
const isMobileMenuOpen = ref(false)
const userMenuRef = ref<HTMLElement>()

// プロフィール画像URL（AuthStoreから取得）
const userProfileImageUrl = computed(() => {
    if (!authStore.isLoggedIn || !authStore.user) return null
    return authStore.user.profile_image?.urls?.small || null
})

// プロフィール画像URLを取得（初回のみ）
const fetchUserProfileImageUrl = async () => {
    if (!authStore.isLoggedIn) return

    try {
        const profile = await $api.profile.get()
        if (profile.data.profile_image?.urls) {
            // AuthStoreのユーザー情報を更新
            authStore.updateUser({
                profile_image: {
                    urls: profile.data.profile_image.urls,
                },
            })
        }
    } catch (error) {
        console.error('プロフィール画像取得エラー:', error)
    }
}

// ユーザーメニュー制御
const toggleUserMenu = () => {
    isUserMenuOpen.value = !isUserMenuOpen.value
}

const closeUserMenu = () => {
    isUserMenuOpen.value = false
}

// モバイルメニュー制御
const toggleMobileMenu = () => {
    isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
    isMobileMenuOpen.value = false
}

// ログアウト処理
const logout = async () => {
    closeUserMenu()
    await authStore.logout()
}

// 外部クリックでメニューを閉じる
const handleClickOutside = (event: Event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target as Node)) {
        closeUserMenu()
    }
}

// イベントリスナー設定
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    fetchUserProfileImageUrl()
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

// 認証状態変更時にプロフィール画像を更新
watch(
    () => authStore.isLoggedIn,
    (isLoggedIn) => {
        if (isLoggedIn) {
            fetchUserProfileImageUrl()
        }
    },
)

// ルート変更時にモバイルメニューを閉じる
watch(
    () => router.currentRoute.value.path,
    () => {
        closeMobileMenu()
    },
)
</script>
