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
        // 1. 分店
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->text('map_url')->nullable();
            $table->string('opening_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. 文章 (最新消息/維修案例)
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('news'); // news, case
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // 3. 預約詢問單
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone');
            $table->string('device_model');
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        // 4. 舊的二手機表 (如果您確定已經全面改用 Shop 系統，這一段可以刪除)
        Schema::create('second_hand_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('specs')->nullable();
            $table->string('condition')->nullable();
            $table->integer('price');
            $table->string('image')->nullable();
            $table->boolean('is_sold')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('second_hand_devices');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('stores');
    }
};
