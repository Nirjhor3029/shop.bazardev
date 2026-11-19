<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('allow_custom_domain')->default(false)->after('allow_subdomain');
        });

        // Matches allow_sslcommerz/allow_pixel_tracking: only Pro ships with this on by default.
        DB::table('plans')->where('slug', 'pro')->update(['allow_custom_domain' => true]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('allow_custom_domain');
        });
    }
};
