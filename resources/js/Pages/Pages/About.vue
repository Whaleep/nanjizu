<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
</script>

<template>
    <Head title="關於我們" />
    <MainLayout>
<div class="bg-gray-900 text-white py-20">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold mb-4">關於ABC</h1>
        <p class="text-xl text-gray-400">專業、誠信、快速 — 您的手機守護者</p>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <!-- 品牌故事 / 簡介 -->
    <div class="max-w-4xl mx-auto mb-16">
        <h2 class="text-3xl font-bold text-center mb-8 text-blue-800">我們的故事</h2>
        <div class="prose max-w-none text-gray-700 leading-relaxed text-lg">
            <p class="mb-6">
                「ABC CER Phone Repair」成立於台北，致力於提供最透明、最專業的手機維修服務。我們深知手機在現代生活中的重要性，因此我們打造了「手機急診室」的概念，強調現場快速維修、面對面檢測，讓顧客看得到過程，修得更安心。
            </p>
            <p>
                不同於傳統維修店價格不透明、維修時間長的問題，男機組建立了完善的線上報價系統，並堅持使用高品質零件。無論是 iPhone、Android 手機，或是 MacBook、iPad、Switch 等電子產品，我們都有專業的技師團隊能為您解決疑難雜症。
            </p>
        </div>
    </div>

    <!-- 我們的優勢 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center border-t-4 border-blue-600">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🚀</div>
            <h3 class="text-xl font-bold mb-3">現場快速維修</h3>
            <p class="text-gray-600">更換電池、螢幕等常見問題，現場最快 30 分鐘即可取件，無需留機過夜，不影響您的日常生活。</p>
        </div>
        <div class="bg-white p-8 rounded-lg shadow-lg text-center border-t-4 border-blue-600">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">🛡️</div>
            <h3 class="text-xl font-bold mb-3">資料安全保障</h3>
            <p class="text-gray-600">維修過程公開透明，絕不偷換零件或查看隱私。面對面維修，讓您全程掌握愛機狀況。</p>
        </div>
        <div class="bg-white p-8 rounded-lg shadow-lg text-center border-t-4 border-blue-600">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">💰</div>
            <h3 class="text-xl font-bold mb-3">價格公開透明</h3>
            <p class="text-gray-600">所有維修項目價格皆在官網公開，現場絕不亂喊價。提供完善的維修後保固，讓您無後顧之憂。</p>
        </div>
    </div>

    <!-- CTA -->
    <div class="bg-blue-50 rounded-2xl p-10 text-center">
        <h2 class="text-2xl font-bold mb-4">手機遇到問題了嗎？</h2>
        <p class="text-gray-600 mb-8">立即查詢報價，或直接與我們聯繫預約檢測。</p>
        <div class="flex flex-col md:flex-row justify-center gap-4">
            <a href="{{ route('repair.index') }}" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                查詢維修價格
            </a>
            <a href="{{ route('stores.index') }}" class="bg-white border border-blue-600 text-blue-600 px-8 py-3 rounded-lg font-bold hover:bg-blue-50 transition">
                查找服務據點
            </a>
        </div>
    </div>
</div>
    </MainLayout>
</template>
