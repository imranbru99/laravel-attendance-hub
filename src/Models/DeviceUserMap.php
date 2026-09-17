<?php

namespace ImranDevBd\AttendanceHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ImranDevBd\AttendanceHub\Traits\BelongsToTenant;

class DeviceUserMap extends Model
{
    use BelongsToTenant;
    protected $table = 'device_user_maps';

    protected $guarded = ['id'];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }
}
