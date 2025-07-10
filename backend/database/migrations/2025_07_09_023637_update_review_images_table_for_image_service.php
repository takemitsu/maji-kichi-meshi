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
        Schema::table('review_images', function (Blueprint $table) {
            // 新しいカラムを追加
            $table->string('filename')->after('review_id');
            $table->string('original_name')->after('filename');
            $table->string('small_path')->after('thumbnail_path');
            $table->integer('file_size')->unsigned()->after('large_path');
            $table->string('mime_type')->after('file_size');

            // 不要になったカラムを削除
            $table->dropColumn('original_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_images', function (Blueprint $table) {
            // カラムを復元
            $table->string('original_path')->after('review_id');

            // 追加したカラムを削除
            $table->dropColumn([
                'filename',
                'original_name',
                'small_path',
                'file_size',
                'mime_type',
            ]);
        });
    }
};
