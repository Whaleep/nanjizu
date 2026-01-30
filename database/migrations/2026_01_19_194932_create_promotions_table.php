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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // type: direct, threshold_cart, threshold_product
            $table->string('type');

            // scope: product, category, tag, all
            $table->string('scope');

            // action_type: percent, fixed, gift, free_shipping
            $table->string('action_type');
            $table->decimal('action_value', 10, 2)->default(0);

            $table->decimal('min_threshold', 10, 2)->default(0); // quantity or amount

            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
