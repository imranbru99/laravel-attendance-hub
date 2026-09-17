<?php

namespace ImranDevBd\AttendanceHub\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \ImranDevBd\AttendanceHub\Drivers\FakeDeviceDriver fake(array $initialPunches = [])
 * @method static bool isFake()
 * @method static \ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface provider(?string $provider = null)
 * @method static \ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface model(string $model)
 * @method static \ImranDevBd\AttendanceHub\Support\DeviceCatalog catalog()
 * @method static \ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface|null autoDetect(string $ip, int $timeout = 3)
 * @method static \ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface device(\ImranDevBd\AttendanceHub\Models\AttendanceDevice|int|string $device)
 * @method static \Illuminate\Support\Collection sync(\ImranDevBd\AttendanceHub\Models\AttendanceDevice|int|string $device, ?\Carbon\CarbonInterface $since = null)
 * @method static \ImranDevBd\AttendanceHub\Support\AttendanceNormalizer normalizer()
 * @method static \ImranDevBd\AttendanceHub\Support\DeviceAutoDetector detector()
 * @method static \ImranDevBd\AttendanceHub\Support\NetworkScanner scanner()
 * @method static \ImranDevBd\AttendanceHub\Support\AttendanceCalculator calculator(?\ImranDevBd\AttendanceHub\DTOs\AttendanceShift $shift = null)
 * @method static \ImranDevBd\AttendanceHub\Support\DeviceTemplateVault vault()
 * @method static \ImranDevBd\AttendanceHub\AttendanceHubManager extend(string $driver, \Closure $callback)
 *
 * @see \ImranDevBd\AttendanceHub\AttendanceHubManager
 */
class AttendanceHub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'attendance-hub';
    }

    /**
     * Determine if the facade is currently running with a fake driver.
     */
    public static function isFake(): bool
    {
        return static::getFacadeRoot()->isFake();
    }
}
