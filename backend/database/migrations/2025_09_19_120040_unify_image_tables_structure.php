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
        // 1. ReviewImageにmoderated_by/atがfillableに追加されるための準備（カラムは既に存在）
        // 特に必要なし（モデルのfillableで対応済み）

        // 2. shop_imagesテーブルにReviewImageと同じパスカラムを追加
        Schema::table('shop_images', function (Blueprint $table) {
            // パスカラム追加（ReviewImageと同じ構造）
            $table->string('thumbnail_path')->nullable()->after('file_size');
            $table->string('small_path')->nullable()->after('thumbnail_path');
            $table->string('medium_path')->nullable()->after('small_path');
            $table->string('large_path')->nullable()->after('medium_path');
            $table->string('original_path')->nullable()->after('large_path');

            // 遅延生成対応
            $table->json('sizes_generated')->nullable()->after('original_path');

            // 検閲カラム統一（ReviewImageと完全一致）
            $table->enum('moderation_status', ['published', 'under_review', 'rejected'])
                ->default('published')
                ->after('sizes_generated');
            $table->text('moderation_notes')->nullable()->after('moderation_status');
        });

        // 3. review_imagesにも遅延生成カラムを追加（まだない場合）
        Schema::table('review_images', function (Blueprint $table) {
            // sizes_generatedカラムを追加
            $table->json('sizes_generated')->nullable()->after('file_size');

            // original_pathカラムを追加
            $table->string('original_path')->nullable()->after('sizes_generated');

            // large_pathをnullableに変更
            $table->string('large_path')->nullable()->change();
        });

        // 4. 既存のShopImageデータを新構造に移行
        // statusからmoderation_statusへデータコピー
        DB::statement("UPDATE shop_images SET moderation_status = COALESCE(status, 'published')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // shop_imagesテーブルの変更を元に戻す
        Schema::table('shop_images', function (Blueprint $table) {
            $table->dropColumn([
                'thumbnail_path',
                'small_path',
                'medium_path',
                'large_path',
                'original_path',
                'sizes_generated',
                'moderation_status',
                'moderation_notes',
            ]);
        });

        // review_imagesテーブルの変更を元に戻す
        Schema::table('review_images', function (Blueprint $table) {
            $table->dropColumn(['sizes_generated', 'original_path']);
            // large_pathはnullableのまま保持（既存のNULL値がある可能性があるため）
            // $table->string('large_path')->nullable(false)->change();
        });
    }
};
