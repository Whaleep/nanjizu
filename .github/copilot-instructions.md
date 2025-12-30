# AI Coding Agent 指南

本指南旨在協助 AI 程式碼代理程式理解 `nanjizu` 程式碼庫的結構、慣例和開發工作流程。

## 1. 專案概述

`nanjizu` 是一個基於 Laravel 框架的 Web 應用程式。它整合了以下前端技術和工具：
- **FilamentPHP**: 用於管理面板。
- **Livewire**: 用於建立動態前端介面。
- **Inertia.js**: 用於將 Vue.js 元件與 Laravel 後端無縫整合。
- **Vue.js**: 用於前端頁面。
- **Tailwind CSS**: 用於快速建構 UI。

## 2. 主要目錄和慣例

-   **`app/Models`**: 包含所有 Eloquent 模型定義，代表應用程式的資料庫結構。
-   **`app/Http/Controllers`**: 包含處理 Web 請求的控制器。
-   **`resources/views`**: 包含 Blade 模板檔案。
-   **`resources/js/Pages`**: 包含所有 Inertia.js (Vue.js) 前端頁面元件。
-   **`database/migrations`**: 包含資料庫遷移檔案，用於定義和修改資料庫結構。
-   **`routes/web.php`**: 定義 Web 路由。
-   **`routes/api.php`**: 定義 API 路由。

## 3. 開發工作流程

以下是開發此專案的關鍵命令：

### 3.1. 設定

在開始開發之前，請確保已安裝所有必要的依賴項並設定資料庫：

1.  **安裝 Composer 依賴項**:
    ```bash
    composer install
    ```
2.  **安裝 Node.js 依賴項**:
    ```bash
    npm install
    ```
3.  **複製環境設定檔**:
    ```bash
    cp .env.example .env
    ```
    然後編輯 `.env` 檔案以設定資料庫連線和其他環境變數。
4.  **產生應用程式金鑰**:
    ```bash
    php artisan key:generate
    ```
5.  **執行資料庫遷移**:
    ```bash
    php artisan migrate
    ```

### 3.2. 執行應用程式

若要執行開發伺服器並監控前端資產的變更：

1.  **啟動 Vite 開發伺服器 (用於前端資產)**:
    ```bash
    npm run dev
    ```
2.  **啟動 Laravel 開發伺服器**:
    ```bash
    php artisan serve
    ```

### 3.3. 測試

執行專案的自動化測試：

```bash
php artisan test
```

## 4. 程式碼慣例

-   **命名空間**: 遵循 PSR-4 自動載入標準。
-   **Vue.js 元件**: 遵循 Vue.js 官方風格指南。
-   **PHP 程式碼**: 遵循 PSR-12 編碼標準。

## 專案功能模組和常用 Middleware

### 功能模組

1.  **會員中心/儀表板 (`DashboardController`)**:
    *   `/` (首頁): 顯示最新訂單。
    *   `/orders`: 訂單列表。
    *   `/orders/{orderNumber}`: 單一訂單詳細資訊。
    *   `/profile`: 使用者個人資料。
    *   `/profile` (POST): 更新個人資料。
2.  **願望清單 (`WishlistController`)**:
    *   `/wishlist`: 使用者願望清單。

### 常用 Middleware

1.  **`web` Middleware 群組**:
    *   所有定義在 `routes/web.php` 中的路由都會自動使用此群組。
    *   包含 `StartSession` (session 處理)、`ShareErrorsFromSession` (錯誤訊息傳遞) 和 `EncryptCookies` (cookie 加密)。
    *   最重要的是，它包含 **`VerifyCsrfToken`**，用於 **CSRF (跨站請求偽造) 保護**，對於所有 POST 請求都至關重要。
2.  **`auth` Middleware**:
    *   用於**驗證使用者是否已登入**。
    *   通常用於保護需要使用者身份驗證才能存取的路由，例如 `/orders`, `/profile`, `/wishlist`。如果使用者未登入，將被導向登入頁面。

---

請隨時提供回饋，以改進此指南的清晰度和完整度。
