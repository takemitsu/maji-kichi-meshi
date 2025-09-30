<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recovery Codes</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    リカバリコード
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Two-Factor Authenticationが有効になりました
                </p>
            </div>

            <div class="bg-white p-8 rounded-lg shadow">
                <div class="mb-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    重要：リカバリコードを安全に保管してください
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>これらのコードは認証アプリにアクセスできない場合の緊急用です。各コードは一度のみ使用できます。</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded border">
                        <h4 class="text-sm font-medium text-gray-900 mb-4">リカバリコード:</h4>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($recoveryCodes as $code)
                                <code class="text-sm font-mono bg-white px-3 py-2 rounded border text-center">{{ $code }}</code>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <button onclick="printCodes()" 
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        📄 印刷する
                    </button>
                    
                    <button onclick="downloadCodes()" 
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        💾 ダウンロード
                    </button>

                    <a href="{{ route('filament.admin.pages.dashboard') }}" 
                       class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        管理画面に移動
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printCodes() {
            const codes = @json($recoveryCodes);
            const printContent = `
                <h2>Two-Factor Authentication Recovery Codes</h2>
                <p>Date: @${new Date().toLocaleDateString('ja-JP')}</p>
                <ul>
                    @${codes.map(code => `<li style="font-family: monospace; padding: 5px;">@${code}</li>`).join('')}
                </ul>
                <p><strong>Warning:</strong> Keep these codes safe. Each code can only be used once.</p>
            `;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head><title>Recovery Codes</title></head>
                    <body>@${printContent}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        function downloadCodes() {
            const codes = @json($recoveryCodes);
            const content = `Two-Factor Authentication Recovery Codes
Generated: @${new Date().toLocaleDateString('ja-JP')}

@${codes.join('\n')}

Warning: Keep these codes safe. Each code can only be used once.`;

            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'recovery-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>