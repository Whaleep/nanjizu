<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'coupon_discount')) {
                $table->integer('coupon_discount')->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'promo_discount')) {
                $table->integer('promo_discount')->default(0)->after('coupon_discount');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->boolean('is_gift')->default(false)->after('subtotal');
            $table->foreignId('promotion_id')->nullable()->after('is_gift')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_discount', 'promo_discount']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn('is_gift');
        });
    }
};
