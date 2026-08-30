<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_sale_id');
            $table->unsignedInteger('number');
            $table->string('receipt_no');
            $table->string('tender', 16);
            $table->unsignedInteger('tendered_vuv');
            $table->unsignedInteger('change_vuv');
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('vat');
            $table->unsignedInteger('total');
            $table->text('receipt_text');
            $table->timestamps();

            $table->unique(['shop_id', 'number']);
            $table->unique(['shop_id', 'client_sale_id']);
            $table->index(['shop_id', 'created_at']);
        });

        Schema::create('sale_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('unit_price_vuv');
            $table->unsignedInteger('line_total_vuv');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_lines');
        Schema::dropIfExists('sales');
    }
};
