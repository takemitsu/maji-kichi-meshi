<template>
  <footer class="bg-gray-50 border-t border-gray-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- メインリンク -->
        <div class="md:col-span-2">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">マジキチメシ</h3>
          <p class="text-sm text-gray-600 mb-4">
            吉祥寺地域の個人的な店舗ランキング作成・共有アプリ
          </p>
          <div class="flex space-x-4">
            <NuxtLink 
              v-if="!authStore.isLoggedIn"
              to="/login" 
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              ログインして始める
            </NuxtLink>
            <NuxtLink 
              v-else
              to="/dashboard" 
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              ダッシュボード
            </NuxtLink>
          </div>
        </div>

        <!-- 機能リンク -->
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-3">機能</h4>
          <ul class="space-y-2">
            <li>
              <NuxtLink 
                v-if="authStore.isLoggedIn"
                to="/shops" 
                class="text-sm text-gray-600 hover:text-gray-900"
              >
                店舗管理
              </NuxtLink>
              <span v-else class="text-sm text-gray-400">店舗管理</span>
            </li>
            <li>
              <NuxtLink 
                v-if="authStore.isLoggedIn"
                to="/reviews" 
                class="text-sm text-gray-600 hover:text-gray-900"
              >
                レビュー
              </NuxtLink>
              <span v-else class="text-sm text-gray-400">レビュー</span>
            </li>
            <li>
              <NuxtLink 
                v-if="authStore.isLoggedIn"
                to="/rankings" 
                class="text-sm text-gray-600 hover:text-gray-900"
              >
                ランキング
              </NuxtLink>
              <span v-else class="text-sm text-gray-400">ランキング</span>
            </li>
          </ul>
        </div>

        <!-- サポート -->
        <div>
          <h4 class="text-sm font-semibold text-gray-900 mb-3">サポート</h4>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-sm text-gray-600 hover:text-gray-900">ヘルプ</a>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-600 hover:text-gray-900">お問い合わせ</a>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-600 hover:text-gray-900">プライバシーポリシー</a>
            </li>
            <li>
              <a href="#" class="text-sm text-gray-600 hover:text-gray-900">利用規約</a>
            </li>
          </ul>
        </div>
      </div>

      <!-- コピーライト -->
      <div class="mt-8 pt-6 border-t border-gray-200">
        <div class="flex flex-col md:flex-row justify-between items-center">
          <p class="text-sm text-gray-500">
            © {{ currentYear }} マジキチメシ. All rights reserved.
          </p>
          <div class="mt-4 md:mt-0 flex space-x-4">
            <!-- ソーシャルリンク（将来用） -->
            <div class="flex space-x-4">
              <!-- Twitter -->
              <a href="#" class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">Twitter</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
              </a>
              <!-- GitHub -->
              <a href="#" class="text-gray-400 hover:text-gray-500">
                <span class="sr-only">GitHub</span>
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
const authStore = useAuthStore()

// 現在の年を取得
const currentYear = new Date().getFullYear()
</script>