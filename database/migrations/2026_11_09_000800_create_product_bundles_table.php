<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pure pivot: seller-curated "frequently bought together" companions for a product. No store_id -
        // both sides are already store-scoped Product rows, so a cross-store pairing can't be created.
        Schema::create('product_bundles', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('companion_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['product_id', 'companion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bundles');
    }
};
