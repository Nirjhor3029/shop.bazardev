<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('options'); // {"Color": "Red", "Size": "M"}
            $table->string('sku', 60)->nullable();
            $table->decimal('price', 12, 2)->nullable();      // null = inherit the product's price
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['store_id', 'product_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('variant_label', 150)->nullable()->after('variant_id'); // snapshot, e.g. "Color: Red, Size: M"
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('product_template', 20)->default('classic')->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('product_template');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('variant_label');
        });
        Schema::dropIfExists('product_variants');
    }
};
