<x-filament-panels::page>
    <div class="space-y-6">
        <!-- ステータス表示 -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">認証ステータス</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Two-Factor Authenticationの現在の状態</p>
                </div>
                <div>
                    @if($this->hasTwoFactorEnabled())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            有効
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            無効
                        </span>
                    @endif
                </div>
            </div>

            @if($this->hasTwoFactorEnabled())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">リカバリコード</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            残り: <span class="font-medium">{{ $this->getRecoveryCodesCount() }}</span> / 8
                        </p>
                        @if($this->getRecoveryCodesCount() <= 2)
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-2 mb-3">
                                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                                    ⚠️ リカバリコードが少なくなっています
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">認証デバイス</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Google Authenticator等のTOTPアプリ
                        </p>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Two-Factor Authenticationが無効です
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                <p>管理者アカウントのセキュリティを向上させるため、Two-Factor Authenticationを有効化することを強く推奨します。</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- アクションボタン -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">アクション</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($this->hasTwoFactorEnabled())
                    <div>
                        <button wire:click="redirectToManage" 
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            詳細設定・管理
                        </button>
                    </div>

                    <div>
                        <button wire:click="redirectToSetup" 
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            新しいデバイスを追加
                        </button>
                    </div>
                @else
                    <div class="md:col-span-2">
                        <button wire:click="redirectToSetup" 
                                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Two-Factor Authenticationを有効化
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- セキュリティ情報 -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        セキュリティについて
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Two-Factor Authenticationは管理者アカウントには必須です</li>
                            <li>リカバリコードは安全な場所に保管してください</li>
                            <li>認証アプリはGoogle Authenticator、Authy等が利用できます</li>
                            <li>デバイスを紛失した場合は、リカバリコードで復旧できます</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>