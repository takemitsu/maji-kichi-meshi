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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('行きたいリストの所有者');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade')->comment('行きたい店舗');
            $table->enum('status', ['want_to_go', 'visited'])->default('want_to_go')->comment('状態: 行きたい/行った');

            // 優先度（★3段階: 1=低, 2=中, 3=高）
            $table->tinyInteger('priority')->unsigned()->default(2)->comment('優先度: 1=いつか, 2=そのうち, 3=絶対');

            // 出典情報（重要: 将来の通知機能用）
            $table->enum('source_type', ['review', 'shop_detail'])->comment('追加経路');
            $table->foreignId('source_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('誰のレビューを見て追加したか');
            $table->foreignId('source_review_id')->nullable()->constrained('reviews')->onDelete('set null')->comment('どのレビューを見て追加したか');

            $table->timestamp('visited_at')->nullable()->comment('訪問日時（行ったに変更した日時）');
            $table->text('memo')->nullable()->comment('メモ（将来機能）');
            $table->timestamps();

            // ユニーク制約: 1ユーザー1店舗1レコード
            $table->unique(['user_id', 'shop_id'], 'unique_user_shop');

            // インデックス
            $table->index(['user_id', 'status', 'priority', 'created_at'], 'idx_wishlist_user_priority');
            $table->index(['user_id', 'status', 'created_at'], 'idx_wishlist_user_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
