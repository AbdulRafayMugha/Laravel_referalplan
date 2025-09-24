<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_email',
        'amount',
        'affiliate_link_id',
        'referrer_id',
        'status',
        'transaction_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function affiliateLink()
    {
        return $this->belongsTo(AffiliateLink::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }
}


