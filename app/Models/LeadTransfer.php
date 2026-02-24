<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'call_id',
        'from_user_id',
        'to_user_id',
        'transferred_at',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'transferred_at' => 'datetime',
        ];
    }
}
