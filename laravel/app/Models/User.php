<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'referrer_id',
        'coordinator_id',
        'referral_code',
        'is_active',
        'email_verified',
        'email_verification_token',
        'email_verification_expires',
        'password_reset_token',
        'password_reset_expires',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
        'password_reset_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verification_expires' => 'datetime',
        'password_reset_expires' => 'datetime',
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function affiliateLinks()
    {
        return $this->hasMany(AffiliateLink::class, 'affiliate_id');
    }

    public function commissions()
    {
        return $this->hasMany(Commission::class, 'affiliate_id');
    }
}
