<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 60);
            $table->string('template', 20)->default('classic'); // classic | combo | video (combo/video added later)
            $table->string('headline', 150)->nullable();
            $table->text('subheadline')->nullable();
            $table->json('benefits')->nullable(); // short bullet list, e.g. ["Free delivery", "7-day return"]
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['store_id', 'slug']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('storefront')->after('status'); // storefront | landing
            $table->foreignId('landing_page_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('landing_page_id');
            $table->dropColumn('source');
        });
        Schema::dropIfExists('landing_pages');
    }
};
