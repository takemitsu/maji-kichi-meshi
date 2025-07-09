<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900 dark:text-gray-100">
                    Two-Factor Authentication Setup
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    管理者はTwo-Factor Authenticationの設定が必要です
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
                <div class="text-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        ステップ 1: QRコードをスキャン
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Google AuthenticatorアプリでQRコードをスキャンしてください
                    </p>
                    
                    <div class="flex justify-center mb-4">
                        {!! $qrCodeSvg !!}
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">手動入力用シークレットキー:</p>
                        <code class="text-sm font-mono bg-white dark:bg-gray-900 dark:text-gray-100 px-2 py-1 rounded border dark:border-gray-600 break-all">{{ $secret }}</code>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            認証コード (6桁)
                        </label>
                        <input type="text" 
                               id="code" 
                               name="code" 
                               maxlength="6" 
                               pattern="[0-9]{6}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-center text-2xl tracking-widest"
                               placeholder="000000"
                               required
                               autocomplete="off">
                        @error('code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            現在のパスワード
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                               required>
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Two-Factor Authenticationを有効化
                        </button>
                    </div>
                </form>

                @if($errors->any())
                    <div class="mt-4 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="text-sm text-red-600">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="text-center">
                <a href="{{ route('filament.admin.pages.dashboard') }}" 
                   class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 text-sm">
                    ← 管理画面に戻る
                </a>
            </div>
        </div>
    </div>
</body>
</html>