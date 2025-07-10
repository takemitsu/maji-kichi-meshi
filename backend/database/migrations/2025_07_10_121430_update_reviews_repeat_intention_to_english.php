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
        // SQLiteでは制約を変更するためにテーブルを再作成する必要がある
        DB::statement('PRAGMA foreign_keys=OFF');

        // 一時テーブルを作成
        Schema::create('reviews_temp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->unsigned();
            $table->enum('repeat_intention', ['yes', 'maybe', 'no']); // 英語版
            $table->text('memo')->nullable();
            $table->date('visited_at');
            $table->timestamps();

            $table->index(['user_id', 'shop_id']);
            $table->index('visited_at');
        });

        // データを変換しながらコピー
        DB::statement("
            INSERT INTO reviews_temp (id, user_id, shop_id, rating, repeat_intention, memo, visited_at, created_at, updated_at)
            SELECT 
                id, user_id, shop_id, rating,
                CASE 
                    WHEN repeat_intention = 'また行く' THEN 'yes'
                    WHEN repeat_intention = 'わからん' THEN 'maybe'
                    WHEN repeat_intention = '行かない' THEN 'no'
                    ELSE repeat_intention
                END,
                memo, visited_at, created_at, updated_at
            FROM reviews
        ");

        // 元テーブルを削除し、一時テーブルをリネーム
        Schema::dropIfExists('reviews');
        Schema::rename('reviews_temp', 'reviews');

        DB::statement('PRAGMA foreign_keys=ON');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 英語データを日本語に変換
        DB::table('reviews')->where('repeat_intention', 'yes')->update(['repeat_intention' => 'また行く']);
        DB::table('reviews')->where('repeat_intention', 'maybe')->update(['repeat_intention' => 'わからん']);
        DB::table('reviews')->where('repeat_intention', 'no')->update(['repeat_intention' => '行かない']);

        // カラムのenum定義を日本語に戻す
        Schema::table('reviews', function (Blueprint $table) {
            $table->enum('repeat_intention', ['また行く', 'わからん', '行かない'])->change();
        });
    }
};
