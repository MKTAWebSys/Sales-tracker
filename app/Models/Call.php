<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    protected $table = 'calls';

    protected $fillable = [
        'company_id',
        'caller_id',
        'handed_over_to_id',
        'called_at',
        'outcome',
        'summary',
        'next_follow_up_at',
        'meeting_planned_at',
    ];

    protected function casts(): array
    {
        return [
            'called_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'meeting_planned_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    public function handedOverTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_to_id');
    }
}
