<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('shop_item_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('currency_type');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('external_tx_id')->nullable()->unique();
            $table->string('payment_method')->nullable();
            $table->longText('payment_response')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('account_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('external_tx_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
