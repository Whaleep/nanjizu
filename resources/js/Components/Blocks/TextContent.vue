<script setup>
defineProps({ data: Object });
</script>

<template>
    <div class="container mx-auto px-4 py-12">
        <div class="prose prose-lg max-w-none mx-auto text-gray-700" v-html="data.body"></div>
    </div>
</template>
