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
        Schema::create('shop_menus', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 顯示名稱 (例如: Apple, 熱銷推薦)

            // 連結類型: category(分類), tag(標籤), link(外部連結)
            $table->string('type')->default('category');

            // 連結目標的 ID (如果是外部連結則存 null)
            $table->unsignedBigInteger('target_id')->nullable();

            // 如果是外部連結，存在這裡
            $table->string('url')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_menus');
    }
};
