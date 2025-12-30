@extends('layouts.app')

@section('title', '維修報價查詢 - ABC')

@section('content')
<!-- 暫時移除 x-cloak 樣式，方便除錯 -->
<style> [x-cloak] { display: none !important; } </style>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-10">選擇您的裝置</h1>

    @foreach($brands as $brand)
        <!-- x-data: activeTab 預設為 'all' -->
        <div class="mb-16 bg-white p-6 rounded-xl shadow-sm border"
             x-data="{ activeTab: 'all' }">

            <!-- 品牌標題 -->
            <div class="flex items-center mb-6 border-b pb-4">
                @if($brand->logo)
                    <img src="/storage/{{ $brand->logo }}" class="h-12 w-12 object-contain mr-4">
                @endif
                <h2 class="text-3xl font-bold text-gray-800">{{ $brand->name }}</h2>
            </div>

            <!-- 系列 Tabs (只有當該品牌有設定系列時才顯示) -->
            @if($brand->deviceCategories->count() > 0)
                <div class="flex flex-wrap gap-2 mb-6">
                    <!-- 全部按鈕 -->
                    <button
                        @click="activeTab = 'all'"
                        :class="activeTab === 'all' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-5 py-2 rounded-full font-bold transition text-sm md:text-base">
                        全部
                    </button>

                    <!-- 各系列按鈕 -->
                    @foreach($brand->deviceCategories as $category)
                        <button
                            @click="activeTab = '{{ $category->id }}'"
                            :class="activeTab == '{{ $category->id }}' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-2 rounded-full font-bold transition text-sm md:text-base">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            <!-- 型號列表區塊 -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                {{-- 直接遍歷品牌下「所有」型號 --}}
                @foreach($brand->deviceModels as $model)
                    <a href="{{ route('repair.show', $model->id) }}"
                       {{-- 篩選邏輯：如果是 'all' 或者 型號的分類ID 等於 當前Tab --}}
                       {{-- 注意：這裡用雙引號包住 category_id，並使用 == 寬鬆比對 --}}
                       x-show="activeTab === 'all' || activeTab == '{{ $model->device_category_id }}'"
                       class="block bg-white border border-gray-200 hover:border-blue-500 hover:shadow-lg transition p-4 rounded-lg text-center group"
                       x-transition:enter="transition ease-out duration-300"
                       x-transition:enter-start="opacity-0 transform scale-95"
                       x-transition:enter-end="opacity-100 transform scale-100">

                        {{-- 顯示所屬系列名稱 (如果有) --}}
                        <span class="block text-xs text-gray-400 mb-1">
                            {{ $model->deviceCategory->name ?? '' }}
                        </span>
                        <span class="font-medium text-lg group-hover:text-blue-600">{{ $model->name }}</span>
                    </a>
                @endforeach
            </div>

            @if($brand->deviceModels->isEmpty())
                <p class="text-gray-500 mt-4 text-center">此品牌目前沒有上架型號。</p>
            @endif
        </div>
    @endforeach
</div>
@endsection
