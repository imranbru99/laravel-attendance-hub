<?php

namespace ImranDevBd\AttendanceHub\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        if (config('attendance-hub.tenancy.enabled', false)) {
            static::creating(function ($model) {
                if (empty($model->tenant_id)) {
                    $resolver = config('attendance-hub.tenancy.tenant_resolver');
                    if (is_callable($resolver)) {
                        $model->tenant_id = call_user_func($resolver);
                    }
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                $resolver = config('attendance-hub.tenancy.tenant_resolver');
                if (is_callable($resolver)) {
                    $tenantId = call_user_func($resolver);
                    if ($tenantId !== null) {
                        $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
                    }
                }
            });
        }
    }
}
