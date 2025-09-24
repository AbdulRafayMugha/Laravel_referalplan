<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('affiliate_id');
            $table->string('type');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['affiliate_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};


