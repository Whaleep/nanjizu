<script setup>
defineProps({ data: Object });
</script>

<template>
    <div class="container mx-auto px-4 py-12">
        <div class="flex flex-col gap-10 items-center"
             :class="data.layout === 'left' ? 'md:flex-row' : 'md:flex-row-reverse'">

            <div class="w-full md:w-1/2">
                <img :src="`/storage/${data.image}`" class="w-full rounded-xl shadow-lg">
            </div>

            <div class="w-full md:w-1/2">
                <div class="prose prose-lg text-gray-700" v-html="data.content"></div>
            </div>
        </div>
    </div>
</template>
