@extends('layouts.app')

@section('title', '嚴選二手機 - 男機組')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-10">嚴選二手機 / 福利品</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($devices as $device)
            <div class="bg-white border rounded-lg overflow-hidden shadow hover:shadow-lg transition flex flex-col group">
                <div class="h-56 bg-gray-100 w-full relative overflow-hidden">
                    @if($device->image)
                        <img src="/storage/{{ $device->image }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    @else
                        <div class="flex items-center justify-center h-full text-gray-400">無圖片</div>
                    @endif
                    @if($device->is_sold)
                        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                            <span class="bg-red-600 text-white px-4 py-1 font-bold rounded transform -rotate-12 border-2 border-white">SOLD OUT</span>
                        </div>
                    @endif
                </div>

                <div class="p-4 flex-grow">
                    <h3 class="font-bold text-lg mb-2">{{ $device->name }}</h3>
                    <p class="text-sm text-gray-500 mb-2 bg-gray-100 inline-block px-2 py-1 rounded">{{ $device->condition }}</p>
                    <div class="mt-2">
                        <p class="text-red-600 font-bold text-xl">NT$ {{ number_format($device->price) }}</p>
                    </div>
                </div>

                <div class="p-4 border-t bg-gray-50 text-center">
                    <!-- 這裡建議換成您的 Line ID -->
                    <a href="https://line.me/ti/p/@yourlineid" target="_blank"
                       class="block w-full {{ $device->is_sold ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600' }} text-white py-2 rounded font-bold transition">
                       {{ $device->is_sold ? '已售出' : 'Line 詢問' }}
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-20 bg-gray-50 rounded-lg">
                <p class="text-gray-500 text-xl">目前沒有上架的二手機，歡迎隨時回來查看。</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
