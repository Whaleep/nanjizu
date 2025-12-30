
# 「男機組」維修與電商系統規格書 (V1 Final)

**版本日期：** 2025-12-20
**目前架構：** Laravel 11 + Filament V3 + Blade + Alpine.js + Tailwind CSS
**V2 目標架構：** Laravel 11 + Filament V3 + **Inertia.js (Vue3/React)** + Tailwind CSS

---

## 1. 資料庫架構 (Database Schema)

這是系統的核心，V2 開發時應沿用此結構（Migrations 可直接複製）。

### A. 核心系統 (System)
*   **users**: 管理員帳號 (Name, Email, Password)。

### B. 內容管理 (CMS)
*   **posts**: 最新消息與維修案例。
    *   `category`: 'news' (消息) | 'case' (案例)
    *   `title`, `slug`, `content`, `image`, `is_published`, `published_at`

### C. 維修報價系統 (Repair Pricing)
*   **brands**: 品牌 (如 Apple, Samsung)。
    *   `name`, `logo`, `sort_order`
*   **device_categories**: 裝置系列 (如 iPhone系列, iPad系列)。
    *   `brand_id`, `name`, `sort_order`
*   **device_models**: 裝置型號 (如 iPhone 15)。
    *   `brand_id`, `device_category_id`, `name`, `slug`, `sort_order`
*   **repair_items**: 維修項目 (如 更換電池)。
    *   `name`, `sort_order`
*   **device_repair_prices**: 報價明細 (關聯表)。
    *   `device_model_id`, `repair_item_id`, `price` (Int), `note` (Nullable)

### D. 電商商品系統 (E-Commerce)
*   **shop_categories**: 商品分類 (無限層級)。
    *   `parent_id` (Self-referencing), `name`, `slug`, `image`, `is_visible`, `sort_order`
*   **shop_menus**: 自訂導航選單 (混合分類與標籤)。
    *   `name`, `type` ('category'|'tag'|'link'), `target_id`, `url`, `sort_order`
*   **product_tags**: 商品標籤 (如 熱銷, 電池)。
    *   `name`, `slug`, `color`
*   **products**: 商品主檔。
    *   `shop_category_id`, `name`, `slug`, `description`, `image`, `is_active`
    *   *關聯*: `tags` (Many-to-Many with `product_product_tag`)
*   **product_variants**: 商品規格/變體 (SKU)。
    *   `product_id`, `name` (規格名), `price`, `stock`, `sku`

### E. 訂單與金流 (Order & Payment)
*   **orders**: 訂單主檔。
    *   `order_number` (Unique, 純英數), `customer_name`, `phone`, `email`, `address`
    *   `total_amount`, `payment_method` (cod/bank/ecpay), `status`, `notes`
*   **order_items**: 訂單明細 (Snapshot)。
    *   `order_id`, `product_variant_id`, `product_name`, `variant_name`, `price`, `quantity`, `subtotal`

---

## 2. 功能模組規格 (Functional Requirements)

### 2.1 後台管理 (Filament Resource)
V2 開發時，後台可以直接沿用 V1 的 Filament 程式碼，幾乎不需要改動。

1.  **維修資料庫群組**：
    *   品牌/系列/型號/維修項目：標準 CRUD。
    *   **批次價格調整**：自訂頁面 (`ManagePricing`)，支援 Excel 匯出/匯入 (Maatwebsite/Excel)。
        *   匯出：支援依品牌/系列篩選。
        *   匯入：支援多工作表 (Sheets)，支援依 Excel 順序更新 `sort_order`。
    *   **列表優化**：型號與維修項目列表支援 Header Action 進行「純文字批量新增」。
2.  **商店管理群組**：
    *   **分類**：支援指定父分類。
    *   **商品**：使用 Repeater 管理多規格 (Variants)，支援多選標籤 (Tags)。
    *   **選單**：`ShopMenu` Resource，支援拖曳排序。
    *   **訂單**：檢視訂單詳情、修改狀態。
3.  **內容管理**：
    *   拆分為 `NewsResource` 與 `RepairCaseResource`，共用 `posts` 表但透過 Scope 區分。

### 2.2 前台功能 (Frontend - V2 重點)
這部分是 V2 需要用 **Inertia (Vue/React)** 重寫的核心區域。

1.  **全站佈局 (Layout)**：
    *   **Navbar**：Logo、電腦版選單 (ShopMenu)、購物車圖示 (即時數量)、手機版漢堡選單。
    *   **Footer**：靜態連結與資訊。
    *   **SEO**：動態 Meta Tags 與 Open Graph。
    *   **懸浮按鈕**：左下角社群連結選單 (Line, FB, IG...)。
2.  **首頁 (Home)**：
    *   Hero Banner (滿版大圖)。
    *   快捷入口 (維修、商店)。
3.  **維修報價 (Repair)**：
    *   品牌列表 -> 系列 Tabs (篩選) -> 型號列表。
    *   型號詳情頁：列出該型號所有維修項目的價格 (依照後台排序)。
4.  **線上商店 (Shop)**：
    *   **導航**：雙層選單 (上方 + 左側)，手機版為側滑抽屜 (Drawer)。
    *   **列表頁**：支援關鍵字搜尋、標籤 (`?tag=`) 篩選、分類篩選。
    *   **商品卡片**：顯示圖片、名稱、價格範圍。
    *   **商品詳情**：
        *   規格選擇器 (點擊切換)。
        *   價格/庫存/按鈕狀態隨規格即時變動。
        *   加入購物車 (AJAX/XHR，不換頁)。
5.  **購物車 (Cart)**：
    *   列表顯示、調整數量、移除商品。
    *   所有操作皆為非同步 (AJAX)，即時更新總金額。
6.  **結帳 (Checkout)**：
    *   填寫收件資訊表單。
    *   選擇付款方式 (貨到付款 / 匯款 / 綠界)。
    *   **綠界串接**：選擇綠界時，送出訂單後自動 POST 表單跳轉至 ECPay。

### 2.3 服務與整合 (Services)
V2 後端邏輯可直接沿用。

1.  **CartService**：Session 驅動的購物車邏輯 (Add, Update, Remove, GetDetails)。
2.  **ECPayService**：產生檢查碼、建立表單 HTML、驗證回調簽章。
3.  **LineBotService**：使用 Messaging API 推播訂單通知 (Flex Message)。

---

## 3. V2 (Inertia.js) 開發建議路徑

如果您決定開啟 `version 2`，以下是建議的步驟：

### 步驟 1：環境建置
1.  **開新專案** (推薦) 或 **開新分支**。
    *   若開新專案：`composer create-project laravel/laravel nanjizu-v2`。
    *   複製 V1 的 `database/migrations` 過去並 migrate。
    *   複製 V1 的 `app/Models` 和 `app/Services`。
2.  **安裝 Inertia**：
    *   後端：`composer require inertiajs/inertia-laravel`。
    *   前端：選擇 **Vue 3** 或 **React** (看您熟悉哪個)。
    *   安裝 Starter Kit (如 Breeze 或 Jetstream) 可以快速幫您設好 Inertia + Tailwind 環境。
    *   指令：`composer require laravel/breeze --dev` -> `php artisan breeze:install vue` (選 Vue)。

### 步驟 2：移植後端邏輯
1.  **Controller 改寫**：
    *   原本 `return view('shop.index', $data)`。
    *   改為 `return Inertia::render('Shop/Index', $data)`。
2.  **API 資源 (選擇性)**：
    *   為了讓前端拿到更乾淨的 JSON，建議使用 `API Resources` (如 `ProductResource`) 來格式化回傳給 Inertia 的資料。

### 步驟 3：重寫前端 (The Hard Part)
1.  **Layout**：用 Vue/React 重新實作 Navbar、Sidebar、Footer。
    *   *挑戰*：手機版側滑選單、購物車下拉、懸浮按鈕的互動邏輯 (原本 Alpine.js 的部分)。
2.  **頁面元件**：
    *   `Shop/Index.vue`
    *   `Shop/Product.vue` (規格選擇邏輯改用 Vue `ref` 或 React `useState`)。
    *   `Cart.vue` (購物車操作改用 Inertia 的 `router.post/visit` 或 `axios`)。

### 步驟 4：狀態管理 (State Management)
*   **購物車紅點**：Inertia 有 "Shared Data" (類似全域變數) 的概念。您需要在 `HandleInertiaRequests` Middleware 中，把 `cartCount` 傳給所有頁面，這樣 Navbar 就能自動更新，不需要寫 `window.dispatchEvent`。

---

