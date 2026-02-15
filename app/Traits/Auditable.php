<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected array $originalAttributes = [];

    public static function bootAuditable(): void
    {
        static::creating(function (Model $model) {
            // No original attributes when creating
        });

        static::created(function (Model $model) {
            app(AuditService::class)->logCreated($model);
        });

        static::updating(function (Model $model) {
            // Store original attributes before update
            $model->originalAttributes = $model->getOriginal();
        });

        static::updated(function (Model $model) {
            $changes = array_diff_assoc(
                $model->getAttributes(),
                $model->originalAttributes
            );

            if (! empty($changes)) {
                app(AuditService::class)->logUpdated(
                    $model,
                    $model->originalAttributes
                );
            }
        });

        static::deleting(function (Model $model) {
            // Check if model uses SoftDeletes and is being force deleted
            $isForceDeleting = method_exists($model, 'isForceDeleting') && $model->isForceDeleting();

            if (! $isForceDeleting) {
                app(AuditService::class)->logDeleted(
                    $model,
                    $model->getAttributes()
                );
            }
        });
    }

    public function audits()
    {
        return app(AuditService::class)->getAuditsFor($this);
    }

    public function getAuditTrail()
    {
        return $this->audits();
    }
}
