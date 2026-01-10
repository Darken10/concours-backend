<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait UsesUuid
{
    /**
     * Boot the trait and assign a UUID to the model when creating.
     */
    protected static function bootUsesUuid(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();

            if (empty($model->{$key})) {
                $model->{$key} = (string) Str::uuid();
            }
        });
    }

    /**
     * Indicates the key type is string.
     */
    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Indicates the IDs are not auto-incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }
}
