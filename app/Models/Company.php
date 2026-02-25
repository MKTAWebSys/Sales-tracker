<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ico',
        'website',
        'status',
        'notes',
        'assigned_user_id',
        'first_caller_user_id',
        'first_caller_assigned_at',
        'first_contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'first_caller_assigned_at' => 'datetime',
            'first_contacted_at' => 'datetime',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function firstCaller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_caller_user_id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function leadTransfers(): HasMany
    {
        return $this->hasMany(LeadTransfer::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function scopeNewUncontacted($query)
    {
        return $query->where('status', 'new')->whereNull('first_contacted_at');
    }

    public function scopeQueuedForCaller($query, int $userId)
    {
        return $query->newUncontacted()->where('first_caller_user_id', $userId);
    }
}
