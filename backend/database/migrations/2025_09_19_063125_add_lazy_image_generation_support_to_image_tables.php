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
        // review_images テーブルに遅延生成サポートを追加
        Schema::table('review_images', function (Blueprint $table) {
            $table->json('sizes_generated')->nullable()->after('large_path');
            $table->string('original_path')->nullable()->after('sizes_generated');
        });

        // shop_images テーブルに遅延生成サポートを追加
        Schema::table('shop_images', function (Blueprint $table) {
            $table->json('sizes_generated')->nullable()->after('filename');
            $table->string('original_path')->nullable()->after('sizes_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_images', function (Blueprint $table) {
            $table->dropColumn(['sizes_generated', 'original_path']);
        });

        Schema::table('shop_images', function (Blueprint $table) {
            $table->dropColumn(['sizes_generated', 'original_path']);
        });
    }
};
