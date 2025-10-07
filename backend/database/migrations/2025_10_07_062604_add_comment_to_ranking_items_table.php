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
        Schema::table('ranking_items', function (Blueprint $table) {
            $table->string('comment', 200)->nullable()->after('rank_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ranking_items', function (Blueprint $table) {
            $table->dropColumn('comment');
        });
    }
};
