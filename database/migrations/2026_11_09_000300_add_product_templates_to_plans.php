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
            $table->json('product_templates')->nullable()->after('storefront_themes'); // null = all templates allowed
        });

        // Free/Subdomain stay on the simple default template; Pro (and any plan the admin
        // marks unlimited) gets every template, matching how landing_templates/storefront_themes work.
        DB::table('plans')->whereIn('slug', ['free', 'subdomain'])->update(['product_templates' => json_encode(['classic'])]);
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('product_templates');
        });
    }
};
