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
        // Reviews table
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('restrict');
            $table->integer('rating')->unsigned(); // 1-5
            $table->enum('repeat_intention', ['yes', 'maybe', 'no']);
            $table->text('memo')->nullable();
            $table->date('visited_at');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'shop_id']);
            $table->index('visited_at');
            $table->index('rating');

            // Note: No unique constraint - users can review the same shop multiple times
        });

        // Review Images table
        Schema::create('review_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('original_name');
            $table->string('large_path');
            $table->string('medium_path');
            $table->string('small_path');
            $table->string('thumbnail_path');
            $table->integer('file_size')->unsigned();
            $table->string('mime_type');
            $table->enum('moderation_status', ['published', 'under_review', 'rejected'])->default('published');
            $table->text('moderation_notes')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('review_id');
            $table->index('moderation_status');
            $table->index(['moderation_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_images');
        Schema::dropIfExists('reviews');
    }
};
