<?php

namespace ImranDevBd\AttendanceHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ImranDevBd\AttendanceHub\Traits\BelongsToTenant;

class DeviceBiometricTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'device_biometric_templates';

    protected $guarded = ['id'];

    protected $casts = [
        'template_data' => 'encrypted',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }
}
