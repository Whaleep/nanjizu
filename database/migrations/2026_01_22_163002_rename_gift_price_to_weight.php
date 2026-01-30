<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. promotion_gifts: gift_price → gift_weight
        if (Schema::hasColumn('promotion_gifts', 'gift_price')) {
            DB::statement('ALTER TABLE promotion_gifts CHANGE gift_price gift_weight DECIMAL(10,2) DEFAULT 1.00');
        }

        // 2. product_variants: 直接刪 gift_value（無資料損失）
        if (Schema::hasColumn('product_variants', 'gift_value')) {
            Schema::table('product_variants', function ($table) {
                $table->dropColumn('gift_value');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 還原
        if (!Schema::hasColumn('promotion_gifts', 'gift_weight')) {
            DB::statement('ALTER TABLE promotion_gifts ADD COLUMN gift_price DECIMAL(10,2) NULL');
        }
        
        Schema::table('product_variants', function ($table) {
            if (!Schema::hasColumn('product_variants', 'gift_value')) {
                $table->decimal('gift_value', 10, 2)->nullable();
            }
        });
    }
};
