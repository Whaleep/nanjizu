<script setup>
import { computed } from 'vue';

const props = defineProps({ data: Object });

// 轉換網址為 Embed URL
const videoInfo = computed(() => {
    let rawUrl = props.data.video_url?.trim();
    if (!rawUrl) return null;

    // 1. 如果貼入的是 <iframe> 代碼，嘗試提取 src
    if (rawUrl.includes('<iframe')) {
        const srcMatch = rawUrl.match(/src=["']([^"']+)["']/);
        if (srcMatch) {
            rawUrl = srcMatch[1];
            // 如果提取出的 src 已經是 facebook plugins 網址，直接回傳
            if (rawUrl.includes('facebook.com/plugins/video.php')) {
                // 檢查是否包含 reel 以決定是否垂直顯示
                const isVerticalReel = rawUrl.includes('%2Freel%2F') || rawUrl.includes('/reel/');
                return {
                    type: 'facebook',
                    isVertical: isVerticalReel,
                    url: rawUrl
                };
            }
        }
    }

    // 2. 處理 Facebook (包含 Reel 和一般網址)
    if (rawUrl.includes('facebook.com') || rawUrl.includes('fb.watch')) {
        const isReel = rawUrl.includes('/reel/');
        const fullUrl = rawUrl.startsWith('http') ? rawUrl : 'https://' + rawUrl;

        return {
            type: 'facebook',
            isVertical: isReel,
            url: `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(fullUrl)}&show_text=false&t=0`
        };
    }

    // 3. 處理 YouTube
    let videoId = null;
    try {
        const parseUrl = rawUrl.startsWith('http') ? rawUrl : 'https://' + rawUrl;
        const u = new URL(parseUrl);

        if (u.hostname.includes('youtu.be')) {
            videoId = u.pathname.slice(1);
        } else if (u.hostname.includes('youtube.com')) {
            if (u.searchParams.has('v')) {
                videoId = u.searchParams.get('v');
            } else if (u.pathname.startsWith('/embed/')) {
                videoId = u.pathname.replace('/embed/', '');
            } else if (u.pathname.startsWith('/reel/') || u.pathname.startsWith('/shorts/')) {
                videoId = u.pathname.split('/')[2];
            }
        }
    } catch (e) {
        // 寬鬆的正則匹配作為備案
        const ytMatch = rawUrl.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
        if (ytMatch) videoId = ytMatch[1];
    }

    if (videoId) {
        return {
            type: 'youtube',
            isVertical: false,
            url: `https://www.youtube.com/embed/${videoId}`
        };
    }

    return null;
});
</script>

<template>
    <div v-if="videoInfo" class="container mx-auto px-4 py-8" :class="videoInfo.isVertical ? 'max-w-md' : 'max-w-4xl'">
        <h3 v-if="data.heading" class="text-2xl font-bold mb-6 text-center text-gray-800">{{ data.heading }}</h3>
        
        <!-- 響應式容器 -->
        <div class="w-full rounded-xl overflow-hidden shadow-2xl border border-gray-100 bg-black transition-all duration-300"
             :class="videoInfo.isVertical ? 'aspect-[9/16]' : 'aspect-video'">
            <iframe 
                :src="videoInfo.url" 
                :title="videoInfo.type === 'youtube' ? 'YouTube video player' : 'Facebook video player'" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                allowfullscreen
                class="w-full h-full"
            ></iframe>
        </div>
    </div>
</template>
