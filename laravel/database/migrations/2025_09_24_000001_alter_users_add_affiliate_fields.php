<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('referrer_id')->nullable()->after('remember_token');
            $table->uuid('coordinator_id')->nullable()->after('referrer_id');
            $table->string('role')->default('affiliate')->after('email');
            $table->string('referral_code', 32)->unique()->after('role');
            $table->boolean('is_active')->default(true)->after('email_verified_at');
            $table->boolean('email_verified')->default(false)->after('is_active');
            $table->string('email_verification_token', 191)->nullable()->after('email_verified');
            $table->timestamp('email_verification_expires')->nullable()->after('email_verification_token');
            $table->string('password_reset_token', 191)->nullable()->after('email_verification_expires');
            $table->timestamp('password_reset_expires')->nullable()->after('password_reset_token');

            $table->index('referrer_id');
            $table->index('coordinator_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referrer_id']);
            $table->dropIndex(['coordinator_id']);
            $table->dropIndex(['role']);

            $table->dropColumn([
                'referrer_id',
                'coordinator_id',
                'role',
                'referral_code',
                'is_active',
                'email_verified',
                'email_verification_token',
                'email_verification_expires',
                'password_reset_token',
                'password_reset_expires',
            ]);
        });
    }
};


