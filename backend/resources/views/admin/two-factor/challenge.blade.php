<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Challenge</title>
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
                    Two-Factor Authentication
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    認証アプリから6桁のコードを入力してください
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow">
                <form method="POST" action="{{ route('admin.two-factor.verify') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">
                            認証コード (6桁)
                        </label>
                        <input type="text" 
                               id="code" 
                               name="code" 
                               maxlength="6" 
                               pattern="[0-9]{6}"
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-center text-2xl tracking-widest"
                               placeholder="000000"
                               required
                               autocomplete="off"
                               autofocus>
                        @error('code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            認証する
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('admin.two-factor.recovery-challenge') }}" 
                       class="text-sm text-blue-600 hover:text-blue-500">
                        リカバリコードを使用する
                    </a>
                </div>

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
        </div>
    </div>
</body>
</html>