@extends('layouts.app')

@section('title', '服務據點 - ABC')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-10">服務據點</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        @foreach($stores as $store)
            <div class="bg-white p-6 rounded-lg shadow-lg border hover:border-blue-400 transition">
                <h2 class="text-2xl font-bold mb-4 text-blue-800 flex items-center gap-2">
                    <span>📍</span> {{ $store->name }}
                </h2>

                <div class="space-y-3 pl-2 border-l-2 border-gray-200 ml-2">
                    <p class="flex items-start">
                        <span class="font-bold w-20 shrink-0 text-gray-600">地址：</span>
                        <span>{{ $store->address }}</span>
                    </p>
                    <p class="flex items-start">
                        <span class="font-bold w-20 shrink-0 text-gray-600">電話：</span>
                        <a href="tel:{{ $store->phone }}" class="text-blue-600 hover:underline font-bold">{{ $store->phone }}</a>
                    </p>
                    <p class="flex items-start">
                        <span class="font-bold w-20 shrink-0 text-gray-600">營業時間：</span>
                        <span>{{ $store->opening_hours }}</span>
                    </p>
                </div>

                <!-- Google Maps Embed -->
                @if($store->map_url)
                    <div class="mt-6 aspect-video w-full rounded-lg overflow-hidden border shadow-inner bg-gray-100">
                        <iframe src="{{ $store->map_url }}" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                @endif

                <div class="mt-6 flex gap-4">
                    <a href="https://www.google.com/maps/dir//{{ $store->address }}" target="_blank"
                       class="flex-1 bg-blue-600 text-white text-center py-2 rounded hover:bg-blue-700 font-bold transition">
                        導航前往
                    </a>
                    <a href="tel:{{ $store->phone }}"
                       class="flex-1 border border-blue-600 text-blue-600 text-center py-2 rounded hover:bg-blue-50 font-bold transition">
                        撥打電話
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
