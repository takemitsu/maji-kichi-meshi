<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Two-Factor Authentication 管理
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    セキュリティ設定を管理してください
                </p>
            </div>

            <!-- 現在のステータス -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">認証ステータス</h3>
                        <p class="text-sm text-gray-500">Two-Factor Authenticationの状態</p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            有効
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- リカバリコード情報 -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">リカバリコード</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            残り: <span class="font-medium">{{ $recoveryCodesCount }}</span> / 8
                        </p>
                        @if($recoveryCodesCount <= 2)
                            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-3">
                                <p class="text-xs text-yellow-700">
                                    ⚠️ リカバリコードが少なくなっています。新しいコードを生成することをお勧めします。
                                </p>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('admin.two-factor.regenerate-recovery-codes') }}">
                            @csrf
                            <div class="mb-3">
                                <input type="password" 
                                       name="password" 
                                       placeholder="現在のパスワード"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                                       required>
                            </div>
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white text-sm py-2 px-3 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                新しいリカバリコードを生成
                            </button>
                        </form>
                    </div>

                    <!-- QRコード再生成 -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">認証アプリ</h4>
                        <p class="text-sm text-gray-600 mb-3">
                            新しいデバイスで設定する場合
                        </p>
                        <a href="{{ route('admin.two-factor.setup') }}" 
                           class="w-full inline-flex justify-center bg-gray-600 text-white text-sm py-2 px-3 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            新しいQRコードを表示
                        </a>
                    </div>
                </div>
            </div>

            <!-- 危険な操作 -->
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-red-900">危険な操作</h3>
                    <p class="text-sm text-red-600">以下の操作は慎重に行ってください</p>
                </div>

                <div class="bg-red-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-red-900 mb-2">Two-Factor Authenticationを無効化</h4>
                    <p class="text-sm text-red-600 mb-4">
                        ⚠️ 無効化すると、アカウントのセキュリティが大幅に低下します。
                    </p>
                    
                    <form method="POST" action="{{ route('admin.two-factor.disable') }}" 
                          onsubmit="return confirm('本当にTwo-Factor Authenticationを無効化しますか？この操作により、アカウントのセキュリティが低下します。')">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                            <div>
                                <input type="password" 
                                       name="password" 
                                       placeholder="現在のパスワード"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 text-sm"
                                       required>
                            </div>
                            <div>
                                <input type="text" 
                                       name="code" 
                                       placeholder="認証コード (6桁)"
                                       maxlength="6"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 text-sm text-center"
                                       required>
                            </div>
                        </div>
                        <button type="submit" 
                                class="w-full bg-red-600 text-white text-sm py-2 px-3 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Two-Factor Authenticationを無効化
                        </button>
                    </form>
                </div>
            </div>

            <!-- ナビゲーション -->
            <div class="flex justify-center space-x-4">
                <a href="{{ route('filament.admin.pages.dashboard') }}" 
                   class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm">
                    管理画面に戻る
                </a>
            </div>

            <!-- エラー表示 -->
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="text-sm text-red-600">
                        @foreach($errors->all() as $error)
                            <p>• {{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 rounded-md p-4">
                    <p class="text-sm text-green-600">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <p class="text-sm text-yellow-600">{{ session('warning') }}</p>
                </div>
            @endif
        </div>
    </div>
</body>
</html>