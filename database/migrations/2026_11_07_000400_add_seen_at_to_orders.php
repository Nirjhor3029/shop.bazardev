<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('seller_seen_at')->nullable()->after('total');
            $table->index(['store_id', 'seller_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'seller_seen_at']);
            $table->dropColumn('seller_seen_at');
        });
    }
};
