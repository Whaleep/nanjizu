@extends('layouts.app')

@section('title', '送修流程 - 男機組')

@section('content')
<div class="container mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold text-center mb-16">維修服務流程</h1>

    <div class="max-w-4xl mx-auto">
        <!-- Step 1 -->
        <div class="flex flex-col md:flex-row items-center mb-12 relative">
            <div class="w-16 h-16 flex items-center justify-center bg-blue-600 text-white rounded-full text-2xl font-bold mb-4 md:mb-0 md:mr-8 shrink-0 shadow-lg z-10 relative">1</div>
            <div class="bg-white p-6 rounded-lg shadow-md flex-grow w-full md:w-auto border-l-4 border-blue-600">
                <h3 class="text-xl font-bold mb-2">線上諮詢 / 預約</h3>
                <p class="text-gray-600">透過網站查價，填寫預約表單，或是加入 Line 官方帳號詢問，初步了解維修價格與所需時間。</p>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="flex flex-col md:flex-row items-center mb-12">
            <div class="w-16 h-16 flex items-center justify-center bg-blue-600 text-white rounded-full text-2xl font-bold mb-4 md:mb-0 md:mr-8 shrink-0 shadow-lg">2</div>
            <div class="bg-white p-6 rounded-lg shadow-md flex-grow w-full md:w-auto border-l-4 border-blue-600">
                <h3 class="text-xl font-bold mb-2">親臨門市 / 寄送</h3>
                <p class="text-gray-600">將裝置帶至最近的分店。若您在外縣市，也支援郵寄維修服務，收到件後會立即與您聯繫。</p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="flex flex-col md:flex-row items-center mb-12">
            <div class="w-16 h-16 flex items-center justify-center bg-blue-600 text-white rounded-full text-2xl font-bold mb-4 md:mb-0 md:mr-8 shrink-0 shadow-lg">3</div>
            <div class="bg-white p-6 rounded-lg shadow-md flex-grow w-full md:w-auto border-l-4 border-blue-600">
                <h3 class="text-xl font-bold mb-2">現場檢測 & 維修</h3>
                <p class="text-gray-600">工程師現場拆機檢測，確認問題後進行維修。一般更換電池、螢幕約 30-60 分鐘即可取件。</p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="flex flex-col md:flex-row items-center mb-12">
            <div class="w-16 h-16 flex items-center justify-center bg-blue-600 text-white rounded-full text-2xl font-bold mb-4 md:mb-0 md:mr-8 shrink-0 shadow-lg">4</div>
            <div class="bg-white p-6 rounded-lg shadow-md flex-grow w-full md:w-auto border-l-4 border-blue-600">
                <h3 class="text-xl font-bold mb-2">完修測試 & 取件</h3>
                <p class="text-gray-600">維修完成後，我們會進行全機功能測試，確認無誤後交還給您，並提供維修保固。</p>
            </div>
        </div>

        <div class="text-center mt-16">
            <a href="{{ route('repair.index') }}" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-bold hover:bg-blue-700 shadow-lg transition transform hover:-translate-y-1">
                立即查詢報價
            </a>
        </div>
    </div>
</div>
@endsection
