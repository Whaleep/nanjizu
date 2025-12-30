@extends('layouts.app')

@section('title', 'ABC - 首頁')

@section('content')
    <!-- Hero Banner (滿版大圖) -->
    <!-- bg-cover: 填滿, bg-center: 置中, bg-no-repeat: 不重複 -->
    <!-- linear-gradient: 加上一層黑色半透明遮罩，讓白字更清晰 -->
    <div class="relative w-full h-[600px] bg-cover bg-center bg-no-repeat flex items-center justify-center"
         style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/images/hero-bg.jpg');">

        <div class="container mx-auto px-4 text-center text-white relative z-10">
            <h1 class="text-4xl md:text-6xl font-bold mb-6 tracking-wide drop-shadow-md">您的手機急診室</h1>
            <p class="text-xl md:text-2xl mb-10 text-gray-200 drop-shadow-md">iPhone / Android / MacBook / iPad 專業快速維修</p>

            <div class="flex flex-col md:flex-row justify-center gap-6">
                <a href="{{ route('repair.index') }}" class="bg-blue-600 border border-blue-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-blue-700 transition shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    查詢維修價格
                </a>
                <a href="{{ route('second-hand.index') }}" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-blue-900 transition shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    找二手機
                </a>
            </div>
        </div>
    </div>

    <!-- 快速服務入口 -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <a href="{{ route('repair.index') }}" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 group-hover:text-white transition">
                    🛠
                </div>
                <h3 class="font-bold text-lg">手機維修</h3>
            </a>
            <a href="{{ route('shop.index') }}" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 group-hover:text-white transition">
                    📱
                </div>
                <h3 class="font-bold text-lg">線上商店</h3>
            </a>
            <a href="{{ route('process') }}" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 group-hover:text-white transition">
                    📦
                </div>
                <h3 class="font-bold text-lg">送修流程</h3>
            </a>
            <a href="{{ route('stores.index') }}" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group">
                <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-600 group-hover:text-white transition">
                    📍
                </div>
                <h3 class="font-bold text-lg">門市據點</h3>
            </a>
        </div>
    </div>

    <!-- 最新消息區塊 -->
    <div class="container mx-auto px-4 py-8 mb-12">
        <h2 class="text-3xl font-bold text-center mb-8">最新消息</h2>

        @if(isset($latestPosts) && $latestPosts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($latestPosts as $post)
                    <div class="bg-white border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                        @if($post->image)
                            <img src="{{ Storage::url($post->image) }}" class="w-full h-48 object-cover">
                        @endif
                        <div class="p-5">
                            <h4 class="font-bold text-lg mb-2 truncate">{{ $post->title }}</h4>
                            <p class="text-gray-500 text-sm mb-4">
                                {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('Y-m-d') : '' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-gray-500">目前沒有最新消息。</p>
        @endif
    </div>
@endsection
