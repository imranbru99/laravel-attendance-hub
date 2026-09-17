<?php

namespace ImranDevBd\AttendanceHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ImranDevBd\AttendanceHub\Traits\BelongsToTenant;

class AttendanceLog extends Model
{
    use BelongsToTenant;
    protected $table = 'attendance_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'punched_at' => 'datetime',
        'location' => 'array',
        'raw_payload' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }
}
