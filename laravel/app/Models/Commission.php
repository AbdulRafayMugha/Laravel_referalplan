<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'affiliate_id',
        'transaction_id',
        'level',
        'amount',
        'rate',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}


