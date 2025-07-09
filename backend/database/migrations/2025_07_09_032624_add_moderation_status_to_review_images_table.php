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
            $table->enum('moderation_status', ['published', 'under_review', 'rejected'])->default('published')->after('large_path');
            $table->text('moderation_notes')->nullable()->after('moderation_status');
            $table->integer('moderated_by')->nullable()->after('moderation_notes');
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_images', function (Blueprint $table) {
            $table->dropColumn(['moderation_status', 'moderation_notes', 'moderated_by', 'moderated_at']);
        });
    }
};
