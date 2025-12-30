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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 優惠碼 (如: SALE2025)
            $table->string('type')->default('fixed'); // fixed (定額), percent (百分比)
            $table->integer('value'); // 100 (折100元) 或 10 (折10%)
            $table->integer('min_spend')->nullable(); // 最低消費金額
            $table->timestamp('start_at')->nullable(); // 開始時間
            $table->timestamp('end_at')->nullable(); // 結束時間
            $table->integer('usage_limit')->nullable(); // 總使用次數限制 (null 代表無限)
            $table->integer('used_count')->default(0); // 已使用次數
            $table->boolean('is_active')->default(true); // 開關
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
