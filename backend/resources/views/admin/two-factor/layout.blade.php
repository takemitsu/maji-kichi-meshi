<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Two-Factor Authentication')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
        
        // Auto-detect dark mode from user preference or system
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <style>
        .dark-mode-aware {
            @apply text-gray-900 dark:text-gray-100;
        }
        .input-dark-mode {
            @apply border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100;
        }
        .bg-card {
            @apply bg-white dark:bg-gray-800;
        }
        .bg-code {
            @apply bg-gray-50 dark:bg-gray-700;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            @yield('content')
        </div>
    </div>
</body>
</html>