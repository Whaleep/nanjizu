# Project Architecture: nanjizu

## Overview

**nanjizu** is a hybrid **Electronics Repair Service** & **E-commerce Platform**.
The system handles device repair inquiries, second-hand device sales, and online shopping for accessories.

## Technology Stack

-   **Backend**: Laravel 11.x (PHP 8.2+)
-   **Admin Panel**: Filament 3.x
-   **Frontend (Legacy V1)**: Blade Templates (being phased out)
-   **Frontend (Modern V2)**: Inertia.js + Vue 3
-   **Styling**: Tailwind CSS
-   **Database**: MySQL

## Directory Structure Highlights

### `app/Models` (Core Entities)

-   **Commerce**: `Product`, `Order`, `Cart`, `Coupon`
-   **Repair**: `DeviceModel`, `RepairItem`, `DeviceRepairPrice`
-   **Content**: `Post` (News/Cases), `Page`, `Store`

### `app/Services` (Business Logic)

-   **CartService**: Shopping cart logic (add/remove/update items).
-   **CheckoutService**: Order creation and processing.
-   **ECPayService**: Payment gateway integration.
-   **RepairService**: Handling repair inquiries and estimates.
-   **LineBotService**: Integration with LINE messaging API.

### `routes/web.php` (Routing Strategy)

-   **V2 (Inertia)**: Main frontend routes (Home, Shop, Cart, Checkout).
-   **V1 (Blade)**: Legacy routes, currently prefixed or commented out.
-   **Admin**: Dedicated routes for printing orders or specific admin actions not covered by Filament.
-   **API/Webhooks**: Payment callbacks and LINE bot webhooks.

## Key Workflows

### 1. Repair Inquiry

User visits Repair page -> Selects Brand/Device/Problem -> Submits Inquiry -> Admin receives notification -> Quote provided.

### 2. E-commerce Checkout

User browses Shop -> Adds to Cart (CartService) -> Checkout (CheckoutService) -> Payment (ECPayService) -> Order Created.

## Development Notes

-   **Inertia Migration**: The project is in the process of migrating from Blade to Inertia/Vue. New features should be implemented in `resources/js/Pages`.
-   **Filament Admin**: Most back-office operations are handled via Filament Resources (`app/Filament/Resources`).
