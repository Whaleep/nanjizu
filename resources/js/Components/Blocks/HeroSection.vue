<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ data: Object });
</script>

<template>
    <div class="relative w-full h-[400px] md:h-[500px] bg-cover bg-center bg-no-repeat flex items-center justify-center"
         :style="`background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('/storage/${data.image}');`">
        <div class="container mx-auto px-4 text-center text-white relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ data.heading }}</h1>
            <p v-if="data.subheading" class="text-xl mb-8">{{ data.subheading }}</p>
            <Link v-if="data.button_url" :href="data.button_url"
                  class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                {{ data.button_text }}
            </Link>
        </div>
    </div>
</template>
