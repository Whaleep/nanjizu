<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import ProductGridLayout from '@/Components/Shop/ProductGridLayout.vue';

const props = defineProps({ data: Object });
const products = ref([]);
const isLoading = ref(true);

onMounted(async () => {
    try {
        // 呼叫後端 API 取得商品
        const response = await axios.get('/api/products/block', {
            params: {
                limit: props.data.limit,
                category_id: props.data.category_id,
                tag_id: props.data.tag_id
            }
        });
        products.value = response.data;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div class="container mx-auto px-4 py-12">
        <h2 class="text-3xl font-bold text-center mb-10">{{ data.heading }}</h2>
        
        <div v-if="isLoading" class="text-center text-gray-500 py-10">載入中...</div>
        
        <!-- 使用共用的 Layout，把資料傳進去 -->
        <ProductGridLayout 
            v-else 
            :products="products" 
            :variant="data.card_variant || 'standard'"
            :show-action="data.show_cart ?? true"
        />
    </div>
</template>
