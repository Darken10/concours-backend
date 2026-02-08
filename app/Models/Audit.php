<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'original_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'original_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAuditedModel(): ?Model
    {
        if (!class_exists($this->model_type)) {
            return null;
        }

        return $this->model_type::find($this->model_id);
    }
}
