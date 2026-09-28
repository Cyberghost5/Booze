<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartyBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'title',
        'slug',
        'description',
        'price',
        'original_price',
        'discount_percentage',
        'badge_text',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'original_price' => 'float',
        'discount_percentage' => 'integer',
        'is_active' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PartyBundleItem::class, 'party_bundle_id');
    }
}
