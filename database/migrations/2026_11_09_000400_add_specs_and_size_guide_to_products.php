<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('description');   // [{key, value}], e.g. [{"key":"Material","value":"Cotton"}]
            $table->text('size_guide')->nullable()->after('specs');    // free-text size chart / fit note, shown only when filled
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['specs', 'size_guide']);
        });
    }
};
