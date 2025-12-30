@extends('layouts.app')

@section('title', $post->title . ' - ABC')
@section('description', Str::limit(strip_tags($post->content), 150)) {{-- 自動抓內容前150字當描述 --}}

@section('og_image', $post->image ? Storage::url($post->image) : asset('images/logo.png')) {{-- 分享時顯示文章封面圖 --}}

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <div class="mb-6 border-b pb-4">
            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                {{ $post->category == 'news' ? '最新消息' : '維修案例' }}
            </span>
            <h1 class="text-3xl font-bold mt-2 mb-2">{{ $post->title }}</h1>
            <p class="text-gray-500 text-sm">
                {{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('Y-m-d H:i') : '' }}
            </p>
        </div>

        @if($post->image)
            <img src="/storage/{{ $post->image }}" class="w-full h-auto rounded-lg mb-8 shadow-sm">
        @endif

        <div class="prose max-w-none text-gray-800 leading-relaxed">
            <!-- 這裡用 !! !! 是為了讓後台富文本編輯器的 HTML 生效 -->
            {!! $post->content !!}
        </div>

        <div class="mt-12 pt-8 border-t flex justify-between">
            <a href="{{ $post->category == 'news' ? route('news.index') : route('cases.index') }}"
               class="text-blue-600 hover:underline font-bold">
                &larr; 返回列表
            </a>
            <a href="{{ route('repair.index') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                立即預約維修
            </a>
        </div>
    </div>
</div>
@endsection
