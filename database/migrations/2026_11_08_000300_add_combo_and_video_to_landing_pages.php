<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->json('combos')->nullable()->after('benefits'); // [{qty, price, label}] - only meaningful when template = combo
            $table->string('video_url', 300)->nullable()->after('combos'); // only meaningful when template = video
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['combos', 'video_url']);
        });
    }
};
