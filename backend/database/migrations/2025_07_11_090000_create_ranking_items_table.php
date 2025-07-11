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
        Schema::create('ranking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranking_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('restrict');
            $table->integer('rank_position');
            $table->timestamps();

            // 同じランキング内で同じ店舗・同じ順位の重複防止
            $table->unique(['ranking_id', 'shop_id'], 'ranking_items_ranking_shop_unique');
            $table->unique(['ranking_id', 'rank_position'], 'ranking_items_ranking_position_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranking_items');
    }
};
