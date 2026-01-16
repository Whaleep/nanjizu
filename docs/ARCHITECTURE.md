# Nanjizu System Architecture

This document provides a technical overview of the product management and storefront systems for developers inheriting this project.

## 🏗 Core Technology Stack

-   **Framework**: Laravel 11
-   **Frontend**: Vue 3 (Composition API) via Inertia.js
-   **Styling**: Tailwind CSS
-   **Admin Panel**: Filament v3 (PHP-driven UI)
-   **Database**: MySQL/PostgreSQL (supports JSON columns for specifications)

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

### 3. Backend Automation (Filament)

The `ProductResource` includes custom header actions:

-   **Generate Variants**: Computes the Cartesian product of defined Visual Options and populates the variant repeater automatically.
-   **Bulk Price Update**: Updates the price for all current variants.
-   **Targeted Price Update**: Updates prices for variants matching a specific attribute (e.g., "All Green shirts to $199").
-   **Product Replication**: Implements a deep copy of the product and all associated variants with slug regeneration.

---

## 🖼 Image Handling Logic

The system minimizes redundant uploads by prioritizing shared images.

### Image Resolution Priority

1. **Variant Image**: If specific SKU has a unique photo (e.g., a shirt with a specific limited print).
2. **Option Value Image**: Shared photo for an attribute (e.g., all "Deep Blue" products showing the same blue fabric photo).
3. **Product Primary Image**: The general product thumbnail used as a final fallback.

### CMS Content Images

The product uses a **Builder (Content Blocks)** system for `description` and `content`. Images in these sections are managed via the Tiptap editor or individual FileUpload blocks within the JSON layout.

---

## ⚡️ Frontend Interactions (`Product.vue`)

### 1. Smart Availability (Cross-Dimension Checking)

The `isOptionValueAvailable` function provides real-time feedback:

-   It checks if a variant exists with the selected attribute combination.
-   It applies visual "disabled" or "strike-through" states to buttons to prevent users from selecting out-of-stock or non-existent combinations.

### 2. Cart Integration

-   **AJAX Add**: Uses Axios for background processing.
-   **Real-time Feedback**: Dispatches `cart-updated` and `show-cart-feedback` events to update the Navbar count and show a confirmation popup without reloading the page.

---

## 🚀 Deployment & SEO

-   **CI/CD**: GitHub Actions (`deploy.yml`) handles building assets and syncing to the production server.
-   **SEO**:
    -   `Product.vue` injects **JSON-LD (Schema.org)** data for Google Rich Results.
    -   Automatic Meta Title/Description generation.
-   **Warmup**: Deployment script includes a site warmup step to prime PHP and Nginx caches.
