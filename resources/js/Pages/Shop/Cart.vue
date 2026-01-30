<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';
import ProductListCard from '@/Components/Shop/ProductListCard.vue';

const props = defineProps({
    cartItems: Array,   // 一般商品
    subtotal: Number,   // 折扣前
    promoDiscount: Number, // 全館/滿額折扣
    appliedPromotions: Array, // 套用的促銷列表
    discount: Number,   // 優惠券折扣
    total: Number,      // 折扣後
    appliedCoupon: String,
});

// 本地狀態
const couponCode = ref('');
const couponMessage = ref('');
const isCouponLoading = ref(false);
const toast = ref({ visible: false, message: '' });

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

const showToast = (message) => {
    toast.value.message = message;
    toast.value.visible = true;
    setTimeout(() => {
        toast.value.visible = false;
    }, 5000); // 5秒後消失
};

// ===== 前端贈品邏輯 =====

// 本地選擇的贈品（不存入後端購物車）格式 { 'promotionId_giftId': quantity }
const selectedGifts = ref({});

const autoSelectGifts = () => {
    // 先暫存一個新的選擇狀態，最後再一次賦值，避免多次觸發響應
    const newSelection = {};

    if (!props.appliedPromotions) return;

    props.appliedPromotions.forEach(promo => {
        // 1. 只處理贈品活動，且必須有額度，還有達標
        if (!promo.is_qualified) return;
        if (promo.action_type !== 'gift' || !promo.allowance || promo.allowance <= 0) return;

        // 2. 關鍵條件：只有 1 項贈品可選
        if (promo.gift_options && promo.gift_options.length === 1) {
            const gift = promo.gift_options[0];
            
            // 檢查基本門檻
            if (gift.stock <= 0) return;
            if (gift.cost > promo.allowance) return;

            // 3. 計算最大可拿數量
            // A. 額度限制: floor(總額度 / 單價)
            let maxQty = Math.floor(promo.allowance / gift.cost);
            
            // B. 活動上限限制
            if (promo.max_gift_count) {
                maxQty = Math.min(maxQty, promo.max_gift_count);
            }
            
            // C. 庫存限制
            maxQty = Math.min(maxQty, gift.stock);

            // 4. 自動加入選擇 (如果數量 > 0)
            if (maxQty > 0) {
                const key = `${promo.id}_${gift.id}`;
                newSelection[key] = maxQty;
            }
        }
    });

    // 更新選擇狀態
    // 注意：這裡我們直接覆蓋 selectedGifts
    // 對於多選一的情況，因為上方邏輯沒跑進去，newSelection 裡不會有該活動的 key，
    // 所以等於自動達成了「多項贈品時清空讓使用者重選」的效果。
    
    // 如果想要保留「多項贈品時，使用者原本選好的不被清空(除非額度不足)」，邏輯會變得很複雜
    // 依照你的需求：「超過 1 項照舊清掉讓他們自己選」，目前的覆蓋邏輯是最符合且最穩定的。
    selectedGifts.value = newSelection;
};

// 1. 監聽購物車變化：如果一般商品數量改變導致 subtotal 變動，強制清空贈品
// 這是最安全的做法，避免「減少商品後額度變少，但手上還抓著高價贈品」的 Bug
watch(() => props.subtotal, (newVal, oldVal) => {
    autoSelectGifts();
});

// 頁面剛進來時，也要執行一次 (讓剛進購物車的人就看到贈品已經選好了)
onMounted(() => {
    autoSelectGifts();
});

// 輔助：計算某個活動目前「已消耗」的額度
const getUsedAllowance = (promoId) => {
    let used = 0;
    Object.entries(selectedGifts.value).forEach(([key, qty]) => {
        const [pId, gId] = key.split('_');
        if (parseInt(pId) == promoId) {
            const promo = props.appliedPromotions.find(p => p.id == promoId);
            const gift = promo?.gift_options.find(g => g.id == parseInt(gId));
            if (gift) {
                used += gift.cost * qty;
            }
        }
    });
    return used;
};

// 輔助：計算某個活動目前「已選擇」的贈品總件數 (用於檢查 max_gift_count)
const getSelectedCount = (promoId) => {
    let count = 0;
    Object.entries(selectedGifts.value).forEach(([key, qty]) => {
        const [pId] = key.split('_');
        if (parseInt(pId) == promoId) {
            count += qty;
        }
    });
    return count;
};

// 2. 產生「可顯示」的贈品列表
// 這是一個扁平化的列表，包含了每個贈品當下「還能不能按 + 號」的邏輯
const displayableGifts = computed(() => {
    const results = [];

    if (!props.appliedPromotions) return [];

    props.appliedPromotions.forEach(promo => {
        // 如果非贈品活動、或是未達標，都不用放贈品
        if (!promo.is_qualified) return;
        if (promo.action_type !== 'gift' || !promo.allowance || promo.allowance <= 0) return;

        const currentUsedAllowance = getUsedAllowance(promo.id);
        const currentSelectedCount = getSelectedCount(promo.id);
        const remainingAllowance = promo.allowance - currentUsedAllowance;

        // 檢查是否已達該活動的總數量上限 (例如：整單限送 1 個)
        const isMaxCountReached = promo.max_gift_count && currentSelectedCount >= promo.max_gift_count;

        promo.gift_options.forEach(gift => {
            // 基礎過濾：庫存 > 0 
            if (gift.stock <= 0) return;
            
            // 關鍵邏輯：如果連選一個的成本都高於總額度，代表根本沒資格選這個，直接不顯示
            if (gift.cost > promo.allowance) return;

            const key = `${promo.id}_${gift.id}`;
            const currentQty = selectedGifts.value[key] || 0;

            // 計算此贈品「還能加幾個 (Max Addable)」
            
            // A. 錢(額度)夠不夠買下一個?
            // 如果 currentQty > 0，remainingAllowance 是扣掉後的，所以要看剩餘夠不夠再買一個
            const affordableNext = remainingAllowance >= gift.cost;

            // B. 數量限制 (針對 max_gift_count)
            const countLimitNotReached = !promo.max_gift_count || (currentSelectedCount < promo.max_gift_count);

            // C. 庫存限制
            const stockAvailable = currentQty < gift.stock;

            // 綜合判斷是否可以按 + 
            const canIncrement = affordableNext && countLimitNotReached && stockAvailable;

            results.push({
                unique_key: key, 
                promotion_id: promo.id,
                promotion_name: promo.name,
                variant_id: gift.id,
                name: gift.name,
                image: gift.image,
                cost: gift.cost,
                quantity: currentQty,
                can_increment: canIncrement,
                stock: gift.stock,
                maxGiftCount: promo.max_gift_count,
                product_slug: gift.slug,

                unit_label: promo.threshold_type === 'quantity' ? '件' : '元',
                allowance_info: {
                    total: promo.allowance,
                    used: currentUsedAllowance,
                    remaining: remainingAllowance,
                    stockAvailable: stockAvailable,
                }
            });
        });
    });

    return results;
});

// 操作：增加/減少贈品 (純前端)
const updateGiftQty = (promoId, variantId, change) => {
    const key = `${promoId}_${variantId}`;
    const currentQty = selectedGifts.value[key] || 0;
    const newQty = currentQty + change;

    // 1. 減少到 0 或以下 -> 移除
    if (newQty <= 0) {
        delete selectedGifts.value[key];
        return;
    }

    // 2. 增加時的檢查 (雖然按鈕 disabled 了，但邏輯層再擋一次)
    const targetGift = displayableGifts.value.find(g => g.unique_key === key);
    if (!targetGift) return; // 異常情況

    if (change > 0 && !targetGift.can_increment) {
        showToast('無法增加：額度不足或超過數量限制');
        return;
    }

    // 3. 更新狀態
    selectedGifts.value[key] = newQty;
};

// 一般商品（非贈品）
const regularItems = computed(() => {
    return props.cartItems.filter(item => !item.is_gift);
});

// 更新一般商品數量
const updateQuantity = async (variantId, newQty) => {
    if (newQty < 0) return;
    try {
        const response = await axios.post('/cart/update', {
            variant_id: variantId,
            quantity: newQty,
            is_gift: false,
            promotion_id: null
        });
        router.reload({ preserveScroll: true }); 
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: response.data.cartCount } }));
    } catch (error) {
        alert(error.response?.data?.message || '更新失敗');
        router.reload();
    }
};

// 移除一般商品
const removeItem = async (variantId) => {
    if (!confirm('確定要移除此商品嗎?')) return;
    try {
        const response = await axios.post('/cart/remove', { variant_id: variantId });
        router.reload();
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: response.data.cartCount } }));
        showToast('商品已移除');
    } catch (error) {
        console.error(error);
    }
};

// 套用優惠券
const applyCoupon = async () => {
    if (!couponCode.value) return;
    isCouponLoading.value = true;

    try {
        await axios.post('/cart/coupon', { code: couponCode.value });
        router.reload({ preserveScroll: true });
        couponMessage.value = '優惠券套用成功！';
        couponCode.value = ''; // 清空輸入框
    } catch (error) {
        couponMessage.value = error.response?.data?.message || '無效的優惠碼';
    } finally {
        isCouponLoading.value = false;
    }
};

// 移除優惠券
const removeCoupon = async () => {
    try {
        await axios.delete('/cart/coupon');
        router.reload({ preserveScroll: true });
    } catch (error) {
        console.error(error);
    }
};

// 因為後端的 discount 是總折扣，所以要減去 promoDiscount 才是優惠券的面額
const couponDiscountAmount = computed(() => {
    return Math.max(0, props.discount - props.promoDiscount);
});

// 前往結帳
const proceedToCheckout = () => {
    // 檢查是否有任何贈品目前是「可增加」狀態 (額度夠、沒超上限、有庫存)
    const hasUnusedGiftQuota = displayableGifts.value.some(gift => gift.can_increment);
    if (hasUnusedGiftQuota) {
        // 使用瀏覽器原生確認視窗
        const userConfirmed = confirm('🎁 您還有贈品能選擇，確定要直接結帳嗎？');
        
        // 如果使用者按「取消」，就中斷結帳流程
        if (!userConfirmed) {
            return;
        }
    }

    router.post('/cart/checkout', {
        selected_gifts: selectedGifts.value
    });
};

</script>

<template>
    <Head title="購物車" />
    <MainLayout>

        <!-- Toast -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform opacity-0 translate-y-2"
            enter-to-class="transform opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform opacity-100 translate-y-0"
            leave-to-class="transform opacity-0 translate-y-2"
        >
            <div v-if="toast.visible" class="fixed top-24 right-4 z-50 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl flex items-center gap-2">
                <span class="text-green-400">✓</span> {{ toast.message }}
            </div>
        </transition>

        <div class="container mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold mb-8 flex items-center gap-2">🛒 您的購物車</h1>

            <div v-if="regularItems.length > 0" class="flex flex-col lg:flex-row gap-10">

                <!-- 購物車列表 (左側) -->
                <div class="lg:w-2/3 space-y-8">
                    
                    <!-- 一般商品列表 -->
                    <div class="space-y-4">
                        <ProductListCard
                            v-for="item in regularItems"
                            :key="item.cart_item_key"
                            :image="item.image"
                            :title="item.product_name"
                            :subtitle="item.variant_name"
                            :price="item.price"
                            :link="`/shop/product/${item.product_slug}`"
                        >
                            <!-- 利用新的 slot 顯示特惠標籤 -->
                            <template #extra-info>
                                <div v-if="item.applicable_promotions && item.applicable_promotions.length > 0" class="flex flex-wrap gap-2">
                                    <template v-for="promoId in item.applicable_promotions" :key="promoId">
                                        
                                        <!-- 使用單元素陣列技巧暫存變數 -->
                                        <template v-for="promo in [appliedPromotions.find(p => p.id === promoId)]">
                                            <div v-if="promo" :key="promo.id" class="group/cart-promo relative">
                                                
                                                <!-- Badge 本體 (帶連結) -->
                                                <Link :href="`/shop?promotion=${promo.id}`" 
                                                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium border transition-colors cursor-pointer relative z-10"
                                                      :class="promo.is_qualified 
                                                        ? 'bg-red-50 text-red-600 border-red-100 hover:bg-red-100' 
                                                        : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-200'">
                                                    <span>{{ promo.is_qualified ? '✅' : '💡' }}</span>
                                                    <span>{{ promo.name }}</span>
                                                </Link>

                                                <!-- Tooltip (顯示計算邏輯) -->
                                                <div class="absolute bottom-full left-0 mb-2 w-max max-w-[240px] p-3 bg-white text-gray-700 text-xs rounded-xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.15)] border border-gray-100 opacity-0 invisible group-hover/cart-promo:opacity-100 group-hover/cart-promo:visible transition-all duration-200 z-50 pointer-events-none transform origin-bottom-left scale-95 group-hover/cart-promo:scale-100">
                                                    
                                                    <div class="font-bold text-sm mb-1 pb-1 border-b border-gray-100 text-gray-900">
                                                        {{ promo.name }}
                                                    </div>

                                                    <!-- 內容：根據達標狀態顯示 -->
                                                    <div v-if="promo.is_qualified" class="text-green-600 font-medium">
                                                        ✅ 條件已滿足
                                                        <div v-if="promo.action_type === 'gift'" class="text-gray-500 font-normal mt-1">
                                                            請至下方贈品區選取贈品
                                                        </div>
                                                        <div v-else class="text-gray-500 font-normal mt-1">
                                                            已折抵 <span class="font-bold text-red-500">NT$ {{ formatPrice(promo.discount_amount) }}</span>
                                                        </div>
                                                    </div>

                                                    <div v-else class="text-orange-500 font-medium">
                                                        💡 尚未滿足條件
                                                        <div class="text-gray-500 font-normal mt-1">
                                                            再買 {{ promo.threshold_type === 'quantity' ? '' : 'NT$' }} 
                                                            <span class="font-bold text-gray-800">{{ formatPrice(Math.max(0, promo.min_threshold - promo.current_total)) }}</span>
                                                            {{ promo.threshold_type === 'quantity' ? '件' : '' }} 
                                                            即可享有優惠
                                                        </div>
                                                    </div>

                                                    <!-- 小箭頭 -->
                                                    <div class="absolute top-full left-4 -mt-[5px] border-8 border-transparent border-t-white"></div>
                                                </div>

                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </template>

                            <template #actions>
                                <div class="flex items-center justify-between sm:justify-end gap-4 w-full">
                                    <div class="flex items-center border border-gray-300 rounded-lg bg-white h-8 md:h-10">
                                        <button @click="updateQuantity(item.variant_id, item.quantity - 1)" 
                                                class="px-2 md:px-3 text-gray-500 hover:bg-gray-100 h-full rounded-l-lg transition">-</button>
                                        <input type="number" :value="item.quantity" @change="updateQuantity(item.variant_id, $event.target.value)"
                                            class="w-10 md:w-12 text-center text-sm border-none focus:ring-0 p-0 h-full">
                                        <button @click="updateQuantity(item.variant_id, item.quantity + 1)" 
                                                :disabled="item.quantity >= item.stock"
                                                class="px-2 md:px-3 text-gray-500 hover:bg-gray-100 h-full rounded-r-lg transition disabled:opacity-50">+</button>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-xs md:text-sm font-bold text-gray-900">
                                            小計: NT$ {{ formatPrice(item.subtotal) }}
                                        </span>
                                        <button @click="removeItem(item.variant_id)" class="text-xs md:text-sm text-gray-400 hover:text-red-500 underline decoration-dotted transition">移除</button>
                                    </div>
                                </div>
                            </template>
                        </ProductListCard>
                    </div>

                    <!-- 贈品專區 (整合在清單中) -->
                    <div v-if="displayableGifts.length > 0" class="mt-8 border-t-2 border-dashed border-green-200 pt-8">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                <span class="text-2xl">🎁</span> 專屬贈品與加購
                            </h2>
                            <!-- <button @click="removeAllGifts" class="text-xs md:text-sm text-gray-400 hover:text-red-500 underline decoration-dotted transition">移除所有贈品</button> -->
                            <!-- <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                修改上方商品將重置贈品
                            </span> -->
                        </div>

                        <div class="space-y-4">
                            <ProductListCard
                                v-for="gift in displayableGifts"
                                :key="gift.unique_key"
                                :image="gift.image"
                                :title="gift.name"
                                :price="0"
                                :link="`/shop/product/${gift.product_slug}`"
                                class="bg-green-50/40 border-green-100"
                            >
                                <template #extra-info>
                                    <div class="flex flex-col gap-1 items-start">
                                        
                                        <!-- 活動名稱 Badge (帶連結與 Tooltip) -->
                                        <div class="group/gift-promo relative inline-block">
                                            <Link :href="`/shop?promotion=${gift.promotion_id}`" 
                                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 transition cursor-pointer">
                                                <span>🎁</span>
                                                <span>{{ gift.promotion_name }}</span>
                                            </Link>

                                            <!-- Tooltip -->
                                            <div class="absolute bottom-full left-0 mb-2 w-max max-w-[220px] p-3 bg-white text-gray-700 text-xs rounded-xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.15)] border border-gray-100 opacity-0 invisible group-hover/gift-promo:opacity-100 group-hover/gift-promo:visible transition-all duration-200 z-50 pointer-events-none">
                                                <div class="font-bold text-sm mb-1 border-b border-gray-100 pb-1 text-green-600">
                                                    {{ gift.promotion_name }}
                                                </div>
                                                <div class="text-gray-500 space-y-1">
                                                    <div>單品兌換需要 <span class="text-gray-900 font-bold">{{ gift.cost }}</span> {{ gift.unit_label }}</div>
                                                    <div>目前已選 {{ gift.quantity }} 個</div>
                                                    
                                                    <div class="border-t border-gray-100 pt-1 mt-1 bg-gray-50 -mx-3 -mb-3 p-2 rounded-b-xl text-xs text-gray-400">
                                                        <span v-if="gift.maxGiftCount && gift.allowance_info.total <= gift.maxGiftCount"> 
                                                            總額度 {{ gift.allowance_info.total }} {{ gift.unit_label }}
                                                        </span>
                                                        <span v-else-if="gift.maxGiftCount">
                                                            最多 {{gift.maxGiftCount}} {{gift.unit_label}}
                                                        </span>
                                                        <span v-else-if="!gift.allowance_info.stockAvailable">
                                                            庫存不足
                                                        </span>
                                                        <br>
                                                        選擇已兌換 {{ gift.allowance_info.used }} {{ gift.unit_label }}
                                                    </div>
                                                </div>
                                                <!-- 小箭頭 -->
                                                <div class="absolute top-full left-4 -mt-[5px] border-8 border-transparent border-t-white"></div>
                                            </div>
                                        </div>

                                        <!-- 簡單的扣除額度提示 (常駐顯示，讓使用者知道代價) -->
                                        <!-- 省略，相信大家都很會算，而且結帳前有提醒 -->
                                        <!-- <div class="text-[10px] text-gray-500 flex items-center gap-2">
                                            <span>扣除: {{ gift.cost }} {{ gift.unit_label }}</span> -->
                                            
                                            <!-- 剩餘額度提示 (條件顯示) -->
                                            <!-- 邏輯：只有當還可以繼續拿 (can_increment) 或者 雖然不能拿但不是因為數量上限 (例如只剩一點點錢) 時才顯示 -->
                                            <!-- 簡化：如果還能拿，顯示剩餘；如果不能拿了，就不顯示，保持乾淨 -->
                                            <!-- <span v-if="gift.can_increment" class="text-green-600 bg-green-50 px-1.5 rounded">
                                                還可選
                                            </span>
                                            <span v-else-if="gift.allowance_info.remaining > 0 && gift.allowance_info.remaining < gift.cost" class="text-orange-400">
                                                餘額不足再選
                                            </span>
                                        </div> -->
                                    </div>
                                </template>

                                <template #actions>
                                    <div class="flex items-center justify-between sm:justify-end gap-4 w-full">
                                        <div class="flex items-center border border-green-300 rounded-lg bg-white h-8 md:h-10 shadow-sm">
                                            <button @click="updateGiftQty(gift.promotion_id, gift.variant_id, -1)" 
                                                :disabled="gift.quantity === 0"
                                                class="px-3 text-green-700 hover:bg-green-50 h-full rounded-l-lg disabled:opacity-30 disabled:cursor-not-allowed font-bold">-</button>
                                            
                                            <span class="w-10 text-center text-sm font-bold text-gray-800 flex items-center justify-center">{{ gift.quantity }}</span>
                                            
                                            <button @click="updateGiftQty(gift.promotion_id, gift.variant_id, 1)" 
                                                :disabled="!gift.can_increment"
                                                class="px-3 text-green-700 hover:bg-green-50 h-full rounded-r-lg disabled:opacity-30 disabled:cursor-not-allowed font-bold">+</button>
                                        </div>
                                    </div>
                                </template>
                            </ProductListCard>
                        </div>
                    </div>
                </div>

                <!-- 結帳區 -->
                <div class="lg:w-1/3">
                    <div class="bg-white shadow rounded-lg p-6 border sticky top-24">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2">訂單摘要</h3>

                        <div class="flex justify-between mb-2 text-gray-600">
                            <span>商品小計</span>
                            <span>NT$ {{ formatPrice(subtotal) }}</span>
                        </div>

                        <!-- 全館/滿額折扣顯示 -->
                        <div v-if="promoDiscount > 0" class="space-y-1 mb-2">
                            <template v-for="promo in appliedPromotions" :key="promo.id">
                                <div v-if="promo.discount_amount > 0" class="flex justify-between text-green-600 font-medium text-sm">
                                    <span>{{ promo.name }}</span>
                                    <span>- NT$ {{ formatPrice(promo.discount_amount) }}</span>
                                </div>
                            </template>
                        </div>

                        <!-- 優惠券折扣顯示 -->
                        <div v-if="couponDiscountAmount > 0" class="flex justify-between mb-2 text-blue-600 font-bold">
                            <span>優惠券 ({{ appliedCoupon }})</span>
                            <span>- NT$ {{ formatPrice(couponDiscountAmount) }}</span>
                        </div>

                        <!-- 滿額贈品提示 (選用，讓摘要區也有贈品感) -->
                        <div v-if="Object.keys(selectedGifts).length > 0" class="flex justify-between mb-2 text-green-600 font-medium bg-green-50 px-2 py-1 rounded">
                            <span>已選贈品</span>
                            <span>{{ Object.keys(selectedGifts).length }} 項</span>
                        </div>

                        <!-- 優惠券輸入區 -->
                        <div class="my-4 pt-4 border-t">
                            <div v-if="!appliedCoupon">
                                <div class="flex gap-2">
                                    <input type="text" v-model="couponCode" placeholder="輸入優惠碼"
                                        class="w-full border rounded px-3 py-2 text-sm uppercase">
                                    <button @click="applyCoupon" :disabled="isCouponLoading"
                                            class="bg-gray-800 text-white px-3 py-2 rounded text-sm hover:bg-gray-700 disabled:opacity-50">
                                        套用
                                    </button>
                                </div>
                                <p v-if="couponMessage" class="text-xs mt-1" :class="couponMessage.includes('成功') ? 'text-green-600' : 'text-red-500'">
                                    {{ couponMessage }}
                                </p>
                            </div>
                            <div v-else class="flex justify-between items-center bg-green-50 p-2 rounded border border-green-200">
                                <span class="text-sm text-green-800">已套用：<b>{{ appliedCoupon }}</b></span>
                                <button @click="removeCoupon" class="text-red-500 text-xs hover:underline">移除</button>
                            </div>
                        </div>

                        <div class="flex justify-between mb-6 text-xl font-bold text-gray-900 border-t pt-4">
                            <span>總金額</span>
                            <span class="text-red-600">NT$ {{ formatPrice(total) }}</span>
                        </div>
                        <button @click="proceedToCheckout" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                            前往結帳
                        </button>
                        <Link href="/shop" class="block w-full text-center py-3 mt-2 text-gray-500 hover:underline">繼續購物</Link>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20 bg-gray-50 rounded-lg border border-dashed">
                <p class="text-xl text-gray-500 mb-6">購物車是空的</p>
                <Link href="/shop" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700">去商店逛逛</Link>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
/* 移除 Chrome, Safari, Edge, Opera 的 type="number" 預設箭頭 */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* 移除 Firefox 的 type="number" 預設箭頭 */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
