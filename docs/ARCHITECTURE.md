# Shop System Architecture

This document provides a technical overview of the product management, promotion engine, and storefront systems for developers inheriting this project.

## 🏗 Core Technology Stack

-   **Framework**: Laravel 11
-   **Frontend**: Vue 3 (Composition API) via Inertia.js
-   **Styling**: Tailwind CSS
-   **Admin Panel**: Filament v3 (PHP-driven UI)
-   **Database**: MySQL/PostgreSQL
-   **State Management**: Hybrid (Server-driven props + LocalStorage for Cart Count)

---

## 🛍 Product & Specification System

The system uses a flexible "Visual Option" approach to handle complex product variants without rigid database table structures.

### 1. Visual Options (Option Groups)

Stored in `products.options` as a JSON column.

-   **Types**: `text` (Chips), `color` (Swatches), `image` (Thumbnails).
-   **Structure**: Each option contains `values` with `label`, `value`, and an optional representative `image`.

### 2. Product Variants

Stored in the `product_variants` table.

-   **Attributes**: A JSON column mapping option names to values (e.g., `{"Color": "#FF0000", "Size": "XL"}`).
-   **Stock & Price**: Managed per individual variant.
-   **Smart Image Logic**: Frontend prioritizes Variant Image > Option Value Image > Product Main Image.

### 3. Backend Automation (Filament)

The `ProductResource` includes custom header actions:

-   **Generate Variants**: Cartesian product generation.
-   **Bulk/Targeted Price Update**: Batch updates for variants.
-   **Product Replication**: Deep copy with slug regeneration.

---

## 🏷️ Promotion Engine (Complex Discounts)

The system supports a robust promotion logic handled by `DiscountService` and `CartService`.

### 1. Allowance vs. Cost Strategy (Threshold Promotions)

Instead of rigid "Buy X get Y" rules, we use a flexible "Budget" system for cart-level promotions.

-   **Allowance**: Calculated by the Backend. The total value (currency or quantity) of qualifying items in the cart.
-   **Cost (Unit Cost)**: The "weight" or "price" of a gift item.
-   **Logic**:
    1.  Backend calculates `allowance` based on cart contents (e.g., bought $5000 worth of Shampoo).
    2.  Frontend receives the `allowance` and the list of available gifts with their `cost`.
    3.  Frontend UI allows users to select gifts as long as `Total Gift Cost <= Allowance`.
    4.  **Auto-Select**: If only 1 gift option exists, the frontend automatically selects the max quantity.

### 2. Promotion Types

-   **Direct**: Applied directly to the product price (Global visibility).
-   **Threshold (Cart)**:
    -   **Action**: Percent Discount, Fixed Amount Discount (Repeatable), or Gift.
    -   **Scope**: Category, Tag, or Specific Products.
    -   **Visibility**: Controlled by `show_badge`. Hidden promotions function in the cart but don't clutter the catalog.

### 3. Safety Mechanisms

-   **Non-Sellable Items**: Products marked `is_sellable = false` cannot be added to the cart via standard API. They serve as Gifts only.
-   **Validation**: `CheckoutController` re-validates all selected gifts against the current cart state before order creation.

---

## 🛒 Cart & Checkout Architecture

### 1. Hybrid Storage

-   **Guest**: Stored in `Session`.
-   **Auth**: Stored in Database (`carts` table).
-   **Merge Logic**: Upon login, Session cart items are merged into the Database cart. **Note**: The merge logic explicitly pulls from Session to avoid duplicating DB items.

### 2. Frontend State (`Cart.vue`)

-   **Reactive Calculation**: The UI calculates gift availability in real-time.
-   **Watchers**: Any change to the cart subtotal triggers a reset or re-calculation of selected gifts to prevent allowance over-usage.
-   **Badges & Tooltips**: Promotion details are shown via clean Badges (🔥/🎁) with rich Tooltips explanation, avoiding UI clutter.

---

## 🔍 Shop Service & Navigation (Refactored)

The `ShopService` handles complex data retrieval to prevent common N+1 and Recursion issues.

### 1. Breadcrumbs (Anti-Recursion)

**Critical Implementation Detail**: We do **NOT** use Eloquent relationships or appends for Breadcrumbs in the API response.
-   **Why**: Circular references (Category Parent <-> Child) cause Infinite Recursion during JSON serialization (500 Error / OOM).
-   **Solution**: Breadcrumbs are calculated manually via a raw query loop in `ShopService`, converted to a flat Array, and passed as a standalone prop.

### 2. Search & Filtering

-   **Unified Filter**: `getIndexData` handles Search (`q`), Tags (`tag`), and Promotions (`promotion={id}`).
-   **Promotion Page**: Visiting `/shop?promotion={id}` triggers a special "Event Banner" mode, filtering products dynamically based on the promotion's scope.

### 3. Dynamic Menu

-   `ShopMenu` supports polymorphic-like targets: Category, Tag, Link, and **Promotion**.
-   Links are resolved in the Backend/Middleware to keep Frontend templates clean.

---

## 🖼 Media Management

-   **Spatie Media Library**: Used for primary images (`Product`, `Variant`, `Category`, `Promotion`).
-   **Custom JSON Cleanup**: A Trait (`HandlesJsonMedia`) parses rich text/JSON fields (Description, ContentBlocks) to manage lifecycle of embedded images.
-   **Frontend Fallback**: `ProductListCard` handles image resolution with graceful fallbacks (Variant -> Option -> Product -> Placeholder).

---

## ⚡️ Deployment & SEO

-   **Schema.org**: `Product.vue` injects JSON-LD for Rich Results.
-   **CI/CD**: GitHub Actions handles asset building.
-   **Slug Handling**: Auto-generated slugs with ID suffixes fallback to prevent empty slugs for non-ASCII (Chinese) names.