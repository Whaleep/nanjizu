<script setup>
defineProps({ data: Object });
</script>

<template>
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <h3 v-if="data.heading" class="text-xl font-bold mb-4 text-gray-800">{{ data.heading }}</h3>

        <div class="border rounded-lg overflow-hidden">
            <table class="w-full text-left border-collapse">
                <tbody>
                    <tr v-for="(item, index) in data.items" :key="index"
                        class="border-b last:border-0 hover:bg-gray-50">
                        <!-- 左欄：項目 -->
                        <td class="bg-gray-100 p-4 w-1/3 font-bold text-gray-700 border-r">
                            {{ item.label }}
                        </td>
                        <!-- 右欄：內容 -->
                        <td class="p-4 w-2/3 text-gray-600">
                            {{ item.value }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
