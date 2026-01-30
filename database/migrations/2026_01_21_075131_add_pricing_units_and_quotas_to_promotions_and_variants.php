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
        Schema::table('promotions', function (Blueprint $table) {
            $table->decimal('gift_quota', 12, 2)->default(0)->comment('贈品總額度');
            $table->string('quota_unit')->default('quantity')->comment('額度單位: quantity, currency');
        });

        Schema::table('promotion_gifts', function (Blueprint $table) {
            $table->decimal('gift_price', 12, 2)->default(1.00)->comment('此贈品佔用的額度');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('pricing_type')->default('currency')->comment('計價類型: currency, points');
            $table->decimal('price_points', 12, 2)->default(0)->comment('點數價格 (預留)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['gift_quota', 'quota_unit']);
        });

        Schema::table('promotion_gifts', function (Blueprint $table) {
            $table->dropColumn('gift_price');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'price_points']);
        });
    }
};
