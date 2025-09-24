<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('customer_email');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('affiliate_link_id')->nullable();
            $table->uuid('referrer_id')->nullable();
            $table->enum('status', ['pending','completed','cancelled','refunded'])->default('completed');
            $table->enum('transaction_type', ['purchase','subscription','upgrade'])->default('purchase');
            $table->timestamps();

            $table->index('affiliate_link_id');
            $table->index('referrer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};


