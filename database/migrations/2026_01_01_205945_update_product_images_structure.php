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
        // 1. 修改 products 表，將 image (string) 改為 images (json)
        // 注意：如果是正式上線專案需寫資料遷移腳本，測試階段我們直接改欄位型態
        Schema::table('products', function (Blueprint $table) {
            // 先把舊欄位改名備份，或者直接 drop 舊的開新的 (這裡示範較安全的新增)
            $table->json('images')->nullable()->after('image');
        });

        // 2. product_variants 新增圖片欄位
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
