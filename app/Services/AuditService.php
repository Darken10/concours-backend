<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(
        Model $model,
        string $action,
        array $originalValues = [],
        array $newValues = []
    ): Audit {
        return Audit::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'original_values' => empty($originalValues) ? null : $originalValues,
            'new_values' => empty($newValues) ? null : $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function logCreated(Model $model): Audit
    {
        return $this->log($model, 'created', [], $model->getAttributes());
    }

    public function logUpdated(Model $model, array $originalValues): Audit
    {
        return $this->log($model, 'updated', $originalValues, $model->getAttributes());
    }

    public function logDeleted(Model $model, array $attributes): Audit
    {
        return $this->log($model, 'deleted', $attributes, []);
    }

    public function logRestored(Model $model): Audit
    {
        return $this->log($model, 'restored', [], $model->getAttributes());
    }

    public function logForceDeleted(Model $model, array $attributes): Audit
    {
        return $this->log($model, 'force_deleted', $attributes, []);
    }

    public function getAuditsFor(Model $model): mixed
    {
        return Audit::where('model_type', get_class($model))
            ->where('model_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAuditsByUser($userId): mixed
    {
        return Audit::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRecentAudits(int $limit = 50): mixed
    {
        return Audit::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
