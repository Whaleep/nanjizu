<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', '男機組') }}</title>

        <!-- 載入 Vite 編譯的資源 -->
        @vite(['resources/js/app.js', "resources/css/app.css"])

        <!-- Inertia 標籤 (這裡會自動生成 meta tags 和掛載點) -->
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-50">
        @inertia
    </body>
</html>
