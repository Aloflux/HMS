<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

trait RecordsAudit
{
    public static function bootRecordsAudit(): void
    {
        static::created(function (Model $model): void {
            AuditLogger::log('created', $model, [
                'attributes' => collect($model->attributesToArray())
                    ->except(['password', 'remember_token'])
                    ->all(),
            ]);
        });

        static::updated(function (Model $model): void {
            $after = collect($model->getChanges())
                ->except(['updated_at', 'password', 'remember_token'])
                ->all();

            if ($after === []) {
                return;
            }

            AuditLogger::log('updated', $model, [
                'before' => collect($model->getOriginal())->only(array_keys($after))->all(),
                'after' => $after,
            ]);
        });

        static::deleted(function (Model $model): void {
            AuditLogger::log('deleted', $model);
        });
    }
}
