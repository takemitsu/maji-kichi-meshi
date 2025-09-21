<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_images', function (Blueprint $table) {
            // 1. shop_id単独のインデックスを作成（外部キー制約のため）
            $table->index('shop_id', 'shop_images_shop_id_index');

            // 2. 複合インデックスを削除
            $table->dropIndex('shop_images_shop_id_status_index');
            $table->dropIndex('shop_images_status_created_at_index');

            // 3. statusカラムを削除
            $table->dropColumn('status');

            // 4. 新しい複合インデックスを作成
            $table->index(['shop_id', 'moderation_status'], 'shop_images_shop_id_moderation_status_index');
            $table->index(['moderation_status', 'created_at'], 'shop_images_moderation_status_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_images', function (Blueprint $table) {
            // 新しいインデックスを削除
            $table->dropIndex(['shop_id', 'moderation_status']);
            $table->dropIndex(['moderation_status', 'created_at']);

            // statusカラムを再作成
            $table->string('status')->default('published')->after('image_sizes');
        });

        // 元のインデックスを再作成
        Schema::table('shop_images', function (Blueprint $table) {
            $table->index(['shop_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // データを復元（moderation_statusからstatusへ）
        DB::statement('UPDATE shop_images SET status = moderation_status');
    }
};
