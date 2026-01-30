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
            // 1. 新增門檻類型：決定是用「金額」還是「數量」來當作計算基礎
            $table->string('threshold_type')->default('amount')->after('type')
                ->comment('amount=滿額(用金額當額度), quantity=滿件(用數量換贈品)');
            
            // 2. 為了相容性，保留 min_threshold 作為「最低觸發門檻」
            // 例如：雖然滿 1 元就能算，但老闆可能希望滿 1000 才能開始選贈品
            
            // 3. 移除 gift_quota (因為額度是動態算的)
            // 建議先 nullable 避免報錯，之後再 drop，或者忽略它
             $table->float('gift_quota')->nullable()->change();

            // 4. 單一訂單上限 (例如：不管買多少，最多送 1 個)
            $table->integer('max_gift_count')->nullable()->after('is_active');
        });

        Schema::table('promotion_gifts', function (Blueprint $table) {
            // 將 gift_weight 改名為 unit_cost (單為成本/扣除額度)
            // 這樣更直觀：滿 5000，A贈品 cost 1000，B贈品 cost 2000
            $table->renameColumn('gift_weight', 'unit_cost'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['threshold_type', 'max_gift_count']);
        });
        Schema::table('promotion_gifts', function (Blueprint $table) {
            $table->renameColumn('unit_cost', 'gift_weight');
        });
    }
};
