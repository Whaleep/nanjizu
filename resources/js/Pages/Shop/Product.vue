<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import axios from 'axios';

const props = defineProps({
    product: Object,
    relatedProducts: Array,
    isWishlisted: Boolean,
    canReview: Boolean,
    reviewStatus: String,
});

// 狀態
const page = usePage();
const selectedVariant = ref(props.product.variants[0] || {});
const isWishlisted = ref(props.isWishlisted);
const quantity = ref(1);
const isLoading = ref(false);
const showToast = ref(false);

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

const priceRange = computed(() => {
    const prices = props.product.variants.map(v => v.price);
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    return min === max ? `NT$ ${formatPrice(min)}` : `NT$ ${formatPrice(min)} ~ ${formatPrice(max)}`;
});

const getMinPrice = (variants) => {
    if (!variants || variants.length === 0) return 0;
    return Math.min(...variants.map(v => v.price));
};

// 加入購物車 (優化版)
const addToCart = async () => {
    if (selectedVariant.value.stock <= 0) return;

    isLoading.value = true;
    try {
        const response = await axios.post('/cart/add', {
            variant_id: selectedVariant.value.id,
            quantity: quantity.value
        });

        // 1. 更新 Navbar 紅點 (透過全域事件，不重整頁面)
        window.dispatchEvent(new CustomEvent('cart-updated', {
            detail: { count: response.data.cartCount }
        }));

        // 2. 顯示右上角提示
        showToast.value = true;
        setTimeout(() => showToast.value = false, 3000);

        // 3. 移除 window.location.reload() <--- 這就是造成閃爍的主因！

    } catch (error) {
        alert('加入失敗: ' + (error.response?.data?.message || '未知錯誤'));
    } finally {
        isLoading.value = false;
    }
};

// === 多圖畫廊邏輯 ===
// 確保 images 是陣列 (相容舊資料)
const galleryImages = computed(() => {
    let imgs = props.product.images || [];
    // 如果舊的單張 image 欄位有值且不在 images 裡，加進去 (過渡期處理)
    if (props.product.image && !imgs.includes(props.product.image)) {
        imgs.unshift(props.product.image);
    }
    return imgs.length > 0 ? imgs : [];
});

const currentImage = ref(galleryImages.value[0] || null);

// 當選中規格改變時，如果該規格有圖片，就切換過去
watch(selectedVariant, (newVal) => {
    if (newVal.image) {
        currentImage.value = newVal.image;
    }
});

// 切換收藏
const toggleWishlist = async () => {
    // 1. 使用 usePage() 獲取全域共享資料
    const page = usePage();
    const user = page.props.auth.user;

    // 2. 判斷 user 是否存在
    if (!user) {
        if(confirm('收藏商品需要先登入會員，是否前往登入？')) {
            window.location.href = '/login';
        }
        return;
    }

    // 3. 執行收藏邏輯 (保持不變)
    try {
        const response = await axios.post('/wishlist/toggle', { product_id: props.product.id });
        isWishlisted.value = response.data.is_wishlisted;
    } catch (error) {
        console.error(error);
        alert('操作失敗，請稍後再試');
    }
};

// 評價表單
const reviewForm = useForm({
    product_id: props.product.id,
    rating: 5,
    comment: '',
});

const submitReview = () => {
    reviewForm.post('/reviews', {
        preserveScroll: true,
        onSuccess: () => {
            // 關鍵修改：檢查後端是否有回傳 success flash
            if (page.props.flash.success) {
                reviewForm.reset('comment');
                alert(page.props.flash.success);
            }
            // 如果是 error flash (例如驗證失敗)，Inertia 雖然視為 onSuccess，但我們不彈出成功
            else if (page.props.flash.error) {
                alert(page.props.flash.error);
            }
        },
        onError: (errors) => {
            alert('提交失敗：' + Object.values(errors).join('\n'));
        }
    });
};

// 引入 Builder Components (如果要在商品頁用 Builder)
import HeroSection from '@/Components/Blocks/HeroSection.vue';
import TextContent from '@/Components/Blocks/TextContent.vue';
import ImageWithText from '@/Components/Blocks/ImageWithText.vue';
import Accordion from '@/Components/Blocks/Accordion.vue';
import Specification from '@/Components/Blocks/Specification.vue';
import ModalBtn from '@/Components/Blocks/ModalBtn.vue';

const components = {
    hero: HeroSection,
    text_content: TextContent,
    image_with_text: ImageWithText,
    accordion: Accordion,
    specification: Specification,
    modal_btn: ModalBtn,
};

// 準備 Schema.org 資料
const schemaData = {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": props.product.name,
    "image": props.product.image ? `${window.location.origin}/storage/${props.product.image}` : '',
    "description": props.product.description ? props.product.description.replace(/<[^>]*>?/gm, '') : '', // 去除 HTML 標籤
    "sku": selectedVariant.value.sku || props.product.id,
    "offers": {
        "@type": "Offer",
        "url": window.location.href,
        "priceCurrency": "TWD",
        "price": selectedVariant.value.price,
        "availability": selectedVariant.value.stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"
    }
};
</script>

<template>
    <Head :title="product.name">
        <!-- 插入 JSON-LD -->
        <component :is="'script'" type="application/ld+json">
            {{ JSON.stringify(schemaData) }}
        </component>
    </Head>

    <ShopLayout>
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform opacity-0 translate-y-2"
            enter-to-class="transform opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform opacity-100 translate-y-0"
            leave-to-class="transform opacity-0 translate-y-2"
        >
            <div v-if="showToast" class="fixed top-20 right-4 z-50 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>已加入購物車！</span>
            </div>
        </transition>

        <!-- 上半部：商品主要資訊區 (永遠保持左右分欄) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">

            <!-- 左側：圖片區 (佔 5 等份，約 41%) -->
            <div class="md:col-span-5">

                <!-- 主圖 -->
                <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden border mb-4 flex items-center justify-center relative">
                    <img v-if="currentImage" :src="`/storage/${currentImage}`" class="w-full h-full object-cover transition-all duration-300">
                    <span v-else class="text-gray-400">No Image</span>
                </div>

                <!-- 縮圖列表 -->
                <div class="flex gap-2 overflow-x-auto pb-2" v-if="galleryImages.length > 1">
                    <button v-for="img in galleryImages" :key="img"
                            @click="currentImage = img"
                            class="w-20 h-20 rounded-lg overflow-hidden border-2 flex-shrink-0"
                            :class="currentImage === img ? 'border-blue-600' : 'border-transparent hover:border-gray-300'">
                        <img :src="`/storage/${img}`" class="w-full h-full object-cover">
                    </button>
                </div>
            </div>

            <!-- 右側：資訊區 (佔 7 等份 = 58%) -->
            <div class="md:col-span-7">
                <nav class="text-sm text-gray-500 mb-4">
                    <Link href="/shop" class="hover:underline">商店</Link> /
                    <Link :href="`/shop/category/${product.category.slug}`" class="hover:underline">{{ product.category.name }}</Link>
                </nav>

                <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>

                <!-- 新增：摘要顯示 -->
                <div v-if="product.excerpt" class="text-gray-600 mb-4 font-medium leading-relaxed whitespace-pre-wrap">
                    {{ product.excerpt }}
                </div>

                <div class="flex items-center gap-2 mb-4">
                    <div class="flex text-yellow-400">
                        <template v-for="i in 5" :key="i">
                            <!-- 實心星星 / 空心星星 簡單判斷 -->
                            <svg v-if="i <= Math.round(product.average_rating)" class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg v-else class="w-5 h-5 text-gray-300 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </template>
                    </div>
                    <span class="text-sm text-gray-500">({{ product.review_count }} 則評價)</span>
                </div>
                <p class="text-gray-500 text-sm mb-4">全系列價格：{{ priceRange }}</p>

                <!-- 當前選中規格的價格 -->
                <div class="mb-6">
                    <div class="text-3xl text-red-600 font-bold mb-2">
                        NT$ {{ formatPrice(selectedVariant.price) }}
                    </div>

                    <!-- 庫存顯示 -->
                    <div class="text-sm">
                        <span class="text-gray-500">庫存狀態：</span>
                        <span v-if="selectedVariant.stock > 10" class="text-green-600 font-bold">庫存充足</span>
                        <span v-else-if="selectedVariant.stock > 0" class="text-orange-500 font-bold">最後 {{ selectedVariant.stock }} 件</span>
                        <span v-else class="text-red-500 font-bold">已售完</span>
                    </div>
                </div>

                <!-- 規格選擇按鈕 -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">規格</h3>
                    <div class="flex flex-wrap gap-3">
                        <button v-for="variant in product.variants" :key="variant.id"
                                @click="selectedVariant = variant"
                                class="px-4 py-2 border rounded-lg font-medium transition flex items-center gap-2"
                                :class="selectedVariant.id === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'hover:border-gray-300 text-gray-700'"
                                :disabled="variant.stock <= 0">
                            <div class="flex items-center gap-2">
                                <!-- 如果規格有圖，顯示小縮圖 -->
                                <img v-if="variant.image" :src="`/storage/${variant.image}`" class="w-6 h-6 rounded-full object-cover border">
                                {{ variant.name }}
                            </div>
                            <span v-if="variant.stock <= 0" class="text-xs text-red-500 ml-1">(缺貨)</span>
                        </button>
                    </div>
                </div>

                <!-- 購買區塊 -->
                <div class="flex gap-4 mb-8">
                    <input type="number" v-model="quantity" min="1" :max="selectedVariant.stock" class="w-32 border rounded-lg px-4 py-3 text-center font-bold">
                    <button @click="addToCart"
                            :disabled="selectedVariant.stock <= 0 || isLoading"
                            class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition shadow-lg flex items-center justify-center gap-2">
                        <svg v-if="isLoading" class="animate-spin h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1010 10A10 10 0 0012 2zm0 18a8 8 0 118-8 8 8 0 01-8 8z" opacity=".3"/><path fill="currentColor" d="M20.24 12.24a8 8 0 01-2.48 5.66" /></svg>
                        {{ isLoading ? '處理中...' : (selectedVariant.stock > 0 ? '加入購物車' : '暫無庫存') }}
                    </button>

                    <!-- 收藏按鈕 -->
                    <button @click="toggleWishlist"
                class="w-12 h-[50px] border rounded-lg flex items-center justify-center transition hover:border-red-400"
                :class="isWishlisted ? 'border-red-500 bg-red-50 text-red-500' : 'border-gray-300 text-gray-400'">
                        <!-- 實心愛心 (已收藏) / 空心愛心 (未收藏) -->
                        <svg v-if="isWishlisted" class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <svg v-else class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                    </button>
                </div>

                <!-- 商品描述 (右側 Builder) -->
                <!-- 這裡原本是 v-html="product.description"，現在要改迴圈 -->
                <div v-if="product.description && product.description.length > 0" class="mt-10 border-t pt-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">商品介紹</h3>
                    <div v-for="(block, index) in product.description" :key="index">
                        <component :is="components[block.type]" v-if="components[block.type]" :data="block.data" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 下方排版 (Builder) -->
        <div v-if="product.content && product.content.length > 0" class="mt-16 border-t pt-10">
            <div v-for="(block, index) in product.content" :key="index">
                <component :is="components[block.type]" v-if="components[block.type]" :data="block.data" />
            </div>
        </div>

        <!-- === 評價區塊 === -->
        <div class="mt-16 border-t pt-10">
            <h2 class="text-2xl font-bold mb-8">商品評價</h2>

            <!-- 1. 評價列表 -->
            <div v-if="product.reviews.length > 0" class="space-y-6 mb-12">
                <div v-for="review in product.reviews" :key="review.id" class="bg-gray-50 p-6 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-bold text-gray-800">{{ review.user.name }}</div>
                        <div class="text-sm text-gray-500">{{ new Date(review.created_at).toLocaleDateString() }}</div>
                    </div>
                    <div class="flex text-yellow-400 mb-3">
                        <template v-for="i in 5">
                            <span v-if="i <= review.rating">★</span>
                            <span v-else class="text-gray-300">★</span>
                        </template>
                    </div>
                    <p class="text-gray-700">{{ review.comment }}</p>
                </div>
            </div>
            <div v-else class="text-gray-500 italic mb-10">目前尚無評價，歡迎分享您的使用心得！</div>

            <!-- 2. 撰寫評價表單 (只有符合資格者顯示) -->
            <div v-if="canReview" class="bg-white border rounded-xl p-6 shadow-sm max-w-2xl">
                <h3 class="text-lg font-bold mb-4">撰寫評價</h3>
                <form @submit.prevent="submitReview">
                    <!-- ... 表單內容保持不變 ... -->

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">整體評分</label>
                        <div class="flex gap-2">
                            <button type="button" v-for="i in 5" :key="i"
                                    @click="reviewForm.rating = i"
                                    class="text-2xl focus:outline-none transition transform hover:scale-110"
                                    :class="i <= reviewForm.rating ? 'text-yellow-400' : 'text-gray-300'">
                                ★
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">心得分享</label>
                        <textarea v-model="reviewForm.comment" rows="4"
                                  class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                  placeholder="說說看這個商品哪裡好用..."></textarea>
                    </div>

                    <button type="submit" :disabled="reviewForm.processing"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition disabled:opacity-50">
                        {{ reviewForm.processing ? '提交中...' : '送出評價' }}
                    </button>
                </form>
            </div>

            <!-- 狀態提示區 -->
            <div v-else class="text-sm p-4 rounded-lg inline-block">
                <!-- 狀態 A: 已經評過了 -->
                <div v-if="reviewStatus === 'reviewed'" class="text-green-600 bg-green-50 border border-green-200">
                    ✅ 您已評價過此商品，感謝您的回饋！
                </div>

                <!-- 狀態 B: 登入但沒買過 (或訂單未完成) -->
                <div v-else-if="reviewStatus === 'no-purchase'" class="text-gray-500 bg-gray-100 border border-gray-200">
                    💡 只有購買過此商品且訂單已完成的會員才能撰寫評價喔。
                </div>

                <!-- 狀態 C: 未登入 -->
                <div v-else-if="reviewStatus === 'guest'" class="text-gray-500 bg-gray-100 border border-gray-200">
                    💡 請先 <Link href="/login" class="text-blue-600 hover:underline">登入</Link> 以撰寫評價。
                </div>
            </div>
        </div>

        <!-- 關聯商品區塊 -->
        <div v-if="relatedProducts.length > 0" class="mt-16 border-t pt-10">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">您可能也喜歡</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <div v-for="related in relatedProducts" :key="related.id"
                     class="bg-white border rounded-xl overflow-hidden hover:shadow-lg transition group">

                    <Link :href="`/shop/product/${related.slug}`" class="block aspect-square bg-gray-100 overflow-hidden relative">
                        <img v-if="related.image" :src="`/storage/${related.image}`"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div v-else class="flex items-center justify-center w-full h-full text-gray-400">無圖</div>
                    </Link>

                    <div class="p-4">
                        <div class="text-xs text-gray-500 mb-1" v-if="related.category">
                            {{ related.category.name }}
                        </div>
                        <h3 class="font-bold text-base mb-2 group-hover:text-blue-600 line-clamp-2">
                            <Link :href="`/shop/product/${related.slug}`">{{ related.name }}</Link>
                        </h3>
                        <p class="text-red-600 font-bold">
                            NT$ {{ formatPrice(getMinPrice(related.variants)) }} 起
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </ShopLayout>
</template>
