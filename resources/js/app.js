import './bootstrap';
import '../css/app.css'; // 引入 CSS

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import axios from 'axios';

// Axios 全域攔截器
window.axios = axios; // 確保全域可用
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// 攔截回應
window.axios.interceptors.response.use(
    response => response,
    error => {
        // 如果狀態碼是 419 (CSRF Token Mismatch)
        if (error.response && error.response.status === 419) {
            // 方法 A: 溫柔提示後重整
            alert('頁面閒置過久，將重新整理以維持連線。');

            // 方法 B (推薦): 直接默默重整，讓使用者無痛接軌
            window.location.reload();

            // 注意：這裡不 return Promise.reject，中斷原本的錯誤訊息
            return new Promise(() => {});
        }
        return Promise.reject(error);
    }
);

createInertiaApp({
    title: (title) => `${title} - 男機組`, // 設定網頁標題格式
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
