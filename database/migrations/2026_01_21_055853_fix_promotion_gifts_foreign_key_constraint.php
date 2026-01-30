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
        Schema::table('promotion_gifts', function (Blueprint $table) {
            // 刪除原本舊的、指向 products 表的外部鍵
            // 注意：名稱可能依然是舊的 promotion_gifts_product_id_foreign
            $table->dropForeign('promotion_gifts_product_id_foreign');

            // 建立新的、指向 product_variants 表的外部鍵
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_gifts', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }
};
