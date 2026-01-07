<script setup>
import { ref } from 'vue';
defineProps({ data: Object }); // data.items 是陣列
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <div class="border rounded-lg divide-y">
            <div v-for="(item, index) in data.items" :key="index">
                <!-- 這裡改用 Vue 的狀態管理，不用 x-data (Alpine) -->
                <Disclosure v-slot="{ open }" :defaultOpen="!!item.is_open">
                    <DisclosureButton class="flex justify-between w-full px-4 py-4 text-left text-sm font-medium text-gray-900 bg-gray-50 hover:bg-gray-100 focus:outline-none focus-visible:ring focus-visible:ring-purple-500 focus-visible:ring-opacity-75 transition">
                        <span>{{ item.title }}</span>
                        <svg :class="open ? 'rotate-180 transform' : ''" class="w-5 h-5 text-gray-500 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </DisclosureButton>

                    <transition
                        enter-active-class="transition duration-1000 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-out"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <DisclosurePanel class="px-4 py-4 bg-white text-gray-600">
                            <!-- 關鍵：加上 prose max-w-none 讓 Tiptap 的 HTML 樣式生效 -->
                            <div class="prose max-w-none" v-html="item.body"></div>
                        </DisclosurePanel>
                    </transition>

                </Disclosure>
            </div>
        </div>
    </div>
</template>

<script>
// 推薦使用 Headless UI 的 Disclosure 元件來做 Accordion，不用自己寫 CSS 動畫
// npm install @headlessui/vue
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
</script>
