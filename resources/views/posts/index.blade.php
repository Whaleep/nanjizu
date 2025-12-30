@extends('layouts.app')

@section('title', $title . ' - ABC')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-10">{{ $title }}</h1>

    @if($posts->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($posts as $post)
                <a href="{{ route('posts.show', $post->slug) }}" class="group block bg-white border rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                    <div class="h-56 overflow-hidden bg-gray-200 relative">
                        @if($post->image)
                            <img src="/storage/{{ $post->image }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        @else
                            <div class="flex items-center justify-center h-full text-gray-400">無圖片</div>
                        @endif
                        <div class="absolute top-2 right-2 bg-blue-600 text-white text-xs px-2 py-1 rounded">
                            {{ $post->category == 'news' ? 'NEWS' : 'CASE' }}
                        </div>
                    </div>
                    <div class="p-6">
                        <h3 class="font-bold text-xl mb-3 group-hover:text-blue-600 transition">{{ $post->title }}</h3>
                        <p class="text-gray-500 text-sm">
                            發布日期：{{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('Y-m-d') : '未定' }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 分頁連結 -->
        <div class="mt-10">
            {{ $posts->links() }}
        </div>
    @else
        <div class="text-center py-20 bg-gray-50 rounded-lg">
            <p class="text-gray-500 text-xl">目前尚無{{ $title }}。</p>
        </div>
    @endif
</div>
@endsection

