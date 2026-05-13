<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesRep extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function buyers(): HasMany
    {
        return $this->hasMany(User::class, 'sales_rep_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function interestRegistrations(): HasMany
    {
        return $this->hasMany(InterestRegistration::class);
    }
}
