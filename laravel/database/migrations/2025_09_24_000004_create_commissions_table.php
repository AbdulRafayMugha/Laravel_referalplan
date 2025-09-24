<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('affiliate_id');
            $table->unsignedBigInteger('transaction_id');
            $table->unsignedTinyInteger('level');
            $table->decimal('amount', 12, 2);
            $table->decimal('rate', 6, 2);
            $table->enum('status', ['pending','approved','paid','cancelled'])->default('approved');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id','transaction_id']);
            $table->index(['status','level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};


