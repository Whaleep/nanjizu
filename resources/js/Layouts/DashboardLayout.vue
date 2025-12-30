<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const page = usePage();
const user = page.props.auth.user;

const menu = [
    { label: '會員總覽', route: '/dashboard', active: page.url === '/dashboard' },
    { label: '我的訂單', route: '/dashboard/orders', active: page.url.startsWith('/dashboard/orders') },
    { label: '我的收藏', route: '/dashboard/wishlist', active: page.url === '/dashboard/wishlist' },
    { label: '個人資料', route: '/dashboard/profile', active: page.url === '/dashboard/profile' },
];
</script>

<template>
    <MainLayout>
        <div class="container mx-auto px-4 py-12">
            <div class="flex flex-col md:flex-row gap-8">

                <!-- 左側選單 -->
                <aside class="w-full md:w-64 flex-shrink-0">
                    <div class="bg-white rounded-lg shadow border overflow-hidden">
                        <div class="p-6 border-b bg-gray-50 text-center">
                            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-3">
                                {{ user.name.charAt(0) }}
                            </div>
                            <h3 class="font-bold text-gray-800">{{ user.name }}</h3>
                            <p class="text-sm text-gray-500">{{ user.email }}</p>
                        </div>
                        <nav class="p-2">
                            <Link v-for="item in menu" :key="item.route"
                                  :href="item.route"
                                  class="block px-4 py-3 rounded-lg transition mb-1"
                                  :class="item.active ? 'bg-blue-50 text-blue-600 font-bold' : 'text-gray-600 hover:bg-gray-50'">
                                {{ item.label }}
                            </Link>

                            <!-- 登出 -->
                            <Link href="/logout" method="post" as="button" class="w-full text-left px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition">
                                登出系統
                            </Link>
                        </nav>
                    </div>
                </aside>

                <!-- 右側內容 -->
                <main class="flex-grow">
                    <slot />
                </main>

            </div>
        </div>
    </MainLayout>
</template>
