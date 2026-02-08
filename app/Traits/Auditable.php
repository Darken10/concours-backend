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

            if (!empty($changes)) {
                app(AuditService::class)->logUpdated(
                    $model,
                    $model->originalAttributes
                );
            }
        });

        static::deleting(function (Model $model) {
            if (!$model->isForceDeleting()) {
                app(AuditService::class)->logDeleted(
                    $model,
                    $model->getAttributes()
                );
            }
        });

        static::forceDeleted(function (Model $model) {
            app(AuditService::class)->logForceDeleted(
                $model,
                $model->getAttributes()
            );
        });

        if (method_exists(Model::class, 'restored')) {
            static::restored(function (Model $model) {
                app(AuditService::class)->logRestored($model);
            });
        }
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
