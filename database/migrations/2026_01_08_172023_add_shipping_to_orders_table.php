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
        // 因為運費可能會變動，這裡不進行關聯，直接快照訂單建立當下的名稱和運費
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_method_name')->nullable()->after('payment_method'); // 存名稱快照
            $table->integer('shipping_fee')->default(0)->after('discount_amount'); // 實際運費
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_method_name', 'shipping_fee']);
        });
    }
};
