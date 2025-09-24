<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('affiliate_id');
            $table->string('link_code', 64)->unique();
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('conversions')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_links');
    }
};


