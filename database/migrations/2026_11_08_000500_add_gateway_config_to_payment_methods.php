<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->json('config')->nullable()->after('instructions');  // non-secret gateway config, e.g. {"store_id": "...", "sandbox": true}
            $table->text('secret')->nullable()->after('config');        // gateway secret (e.g. SSLCommerz store_passwd), encrypted at rest
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['config', 'secret']);
        });
    }
};
