<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoFactor extends Model
{
    use HasFactory;

    protected $table = 'user_two_factors';

    protected $fillable = [
        'user_id',
        'method',
        'secret_enc',
        'backup_codes',
        'enabled_at',
    ];

    protected $casts = [
        'backup_codes' => 'array',
        'enabled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
