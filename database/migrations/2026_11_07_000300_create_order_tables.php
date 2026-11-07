<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('number', 16)->unique();               // shown to the customer
            $table->string('token', 40)->unique();                // secret for the public order page
            $table->string('status', 12)->default('pending');      // pending | confirmed | shipped | delivered | cancelled
            $table->string('payment_method', 12);                  // cod | bkash | nagad | rocket
            $table->string('payment_status', 12)->default('unpaid'); // unpaid | verifying | paid | failed
            $table->string('trx_id', 40)->nullable();              // manual payment: transaction id from the customer
            $table->string('payer_number', 20)->nullable();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->text('address');
            $table->text('note')->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
            $table->index(['store_id', 'status', 'created_at']);
            $table->index(['store_id', 'customer_phone']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);                           // snapshot, product may change or disappear later
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
