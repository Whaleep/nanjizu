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
        // Targets: Product
        Schema::create('promotion_product', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
        });

        // Targets: Category
        Schema::create('promotion_shop_category', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_category_id')->constrained()->onDelete('cascade');
        });

        // Targets: Tag
        Schema::create('promotion_product_tag', function (Blueprint $table) {
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_tag_id')->constrained()->onDelete('cascade');
        });

        // Gift Options (available to choose from)
        Schema::create('promotion_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_product');
        Schema::dropIfExists('promotion_shop_category');
        Schema::dropIfExists('promotion_product_tag');
        Schema::dropIfExists('promotion_gifts');
    }
};
