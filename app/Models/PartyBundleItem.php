<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyBundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_bundle_id',
        'product_id',
        'quantity',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(PartyBundle::class, 'party_bundle_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
