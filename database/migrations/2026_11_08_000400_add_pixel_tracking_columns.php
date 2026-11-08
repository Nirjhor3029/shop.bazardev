<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('pixel_id', 30)->nullable()->after('video_url'); // Meta Pixel ID (seller's own)
            $table->text('capi_token')->nullable()->after('pixel_id');      // Meta Conversions API access token, encrypted at rest
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('fbclid', 120)->nullable()->after('landing_page_id');
            $table->string('fbp', 60)->nullable()->after('fbclid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['fbclid', 'fbp']);
        });
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['pixel_id', 'capi_token']);
        });
    }
};
