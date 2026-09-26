<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'phone_verified_at',
        'phone_otp',
        'phone_otp_expires_at',
        'store_name',
        'default_address',
        'default_latitude',
        'default_longitude',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'phone_otp',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function phone(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => \App\Services\PhoneNumberService::normalize($value),
        );
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor_admin';
    }

    public function isConsumer(): bool
    {
        return $this->role === 'consumer';
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'vendor_id');
    }

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }
}
