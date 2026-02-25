<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
