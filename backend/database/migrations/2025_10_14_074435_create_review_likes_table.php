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
        Schema::create('review_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('いいねしたユーザー');
            $table->foreignId('review_id')->constrained()->cascadeOnDelete()->comment('いいね対象のレビュー');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'review_id'], 'unique_user_review');
            $table->index('review_id', 'idx_review_id'); // いいね数集計用
            $table->index(['user_id', 'created_at'], 'idx_user_created'); // ユーザーのいいねリスト表示用
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_likes');
    }
};
