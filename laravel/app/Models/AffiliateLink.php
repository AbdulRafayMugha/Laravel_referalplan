<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'link_code',
        'clicks',
        'conversions',
        'is_active',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'conversions' => 'integer',
        'is_active' => 'boolean',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }
}


