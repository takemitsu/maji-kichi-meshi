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
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_image_filename')->nullable()->after('email');
            $table->string('profile_image_original_name')->nullable()->after('profile_image_filename');
            $table->string('profile_image_thumbnail_path')->nullable()->after('profile_image_original_name');
            $table->string('profile_image_small_path')->nullable()->after('profile_image_thumbnail_path');
            $table->string('profile_image_medium_path')->nullable()->after('profile_image_small_path');
            $table->string('profile_image_large_path')->nullable()->after('profile_image_medium_path');
            $table->integer('profile_image_file_size')->unsigned()->nullable()->after('profile_image_large_path');
            $table->string('profile_image_mime_type')->nullable()->after('profile_image_file_size');
            $table->timestamp('profile_image_uploaded_at')->nullable()->after('profile_image_mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_image_filename',
                'profile_image_original_name',
                'profile_image_thumbnail_path',
                'profile_image_small_path',
                'profile_image_medium_path',
                'profile_image_large_path',
                'profile_image_file_size',
                'profile_image_mime_type',
                'profile_image_uploaded_at',
            ]);
        });
    }
};
