<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        app(AuditLogService::class)->recordModelEvent($model, 'created');
    }

    public function updated(Model $model): void
    {
        app(AuditLogService::class)->recordModelEvent($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        app(AuditLogService::class)->recordModelEvent($model, 'deleted');
    }
}
