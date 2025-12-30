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
        // 1. 品牌
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. 產品系列 (隸屬品牌)
        Schema::create('device_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. 裝置型號 (隸屬品牌與系列)
        Schema::create('device_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 4. 維修項目
        Schema::create('repair_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 5. 價格表 (Pivot Table)
        Schema::create('device_repair_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_item_id')->constrained()->cascadeOnDelete();
            $table->integer('price');
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_repair_prices');
        Schema::dropIfExists('repair_items');
        Schema::dropIfExists('device_models');
        Schema::dropIfExists('device_categories');
        Schema::dropIfExists('brands');
    }
};
