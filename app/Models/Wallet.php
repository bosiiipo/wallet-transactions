<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'currency', 'balance'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
