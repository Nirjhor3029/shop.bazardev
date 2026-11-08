<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 40)->unique();
            $table->decimal('price', 10, 2)->default(0); // taka per month
            $table->unsignedInteger('max_products')->nullable();      // null = unlimited
            $table->unsignedInteger('max_landing_pages')->nullable(); // null = unlimited
            $table->json('landing_templates')->nullable();            // null = all templates allowed
            $table->json('storefront_themes')->nullable();            // null = all themes allowed
            $table->boolean('allow_subdomain')->default(false);       // {slug}.shop.bazardev.com vs shop.bazardev.com/{slug}
            $table->boolean('allow_sslcommerz')->default(false);
            $table->boolean('allow_pixel_tracking')->default(false);
            $table->boolean('remove_branding')->default(false);
            $table->boolean('is_default')->default(false); // assigned to every new store at signup
            $table->boolean('is_active')->default(true);   // inactive plans stay assigned but can't be newly picked
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->timestamp('plan_expires_at')->nullable()->after('plan_id'); // null = never expires (e.g. Free)
        });

        $now = now();
        $freeId = DB::table('plans')->insertGetId([
            'name' => 'Free', 'slug' => 'free', 'price' => 0,
            'max_products' => 20, 'max_landing_pages' => 1,
            'landing_templates' => json_encode(['classic']), 'storefront_themes' => json_encode(['minimal', 'boutique']),
            'allow_subdomain' => false, 'allow_sslcommerz' => false, 'allow_pixel_tracking' => false, 'remove_branding' => false,
            'is_default' => true, 'is_active' => true, 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('plans')->insert([
            [
                'name' => 'Subdomain', 'slug' => 'subdomain', 'price' => 10,
                'max_products' => 20, 'max_landing_pages' => 1,
                'landing_templates' => json_encode(['classic']), 'storefront_themes' => json_encode(['minimal', 'boutique']),
                'allow_subdomain' => true, 'allow_sslcommerz' => false, 'allow_pixel_tracking' => false, 'remove_branding' => false,
                'is_default' => false, 'is_active' => true, 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'name' => 'Pro', 'slug' => 'pro', 'price' => 599,
                'max_products' => null, 'max_landing_pages' => null,
                'landing_templates' => null, 'storefront_themes' => null,
                'allow_subdomain' => true, 'allow_sslcommerz' => true, 'allow_pixel_tracking' => true, 'remove_branding' => true,
                'is_default' => false, 'is_active' => true, 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // existing stores (created before plans existed) default to Free, same behaviour as a fresh signup would get
        DB::table('stores')->whereNull('plan_id')->update(['plan_id' => $freeId]);
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn('plan_expires_at');
        });
        Schema::dropIfExists('plans');
    }
};
