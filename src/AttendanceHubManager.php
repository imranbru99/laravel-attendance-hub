<?php

namespace ImranDevBd\AttendanceHub;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\Drivers\AdmsPushDriver;
use ImranDevBd\AttendanceHub\Drivers\DahuaDriver;
use ImranDevBd\AttendanceHub\Drivers\FakeDeviceDriver;
use ImranDevBd\AttendanceHub\Drivers\HikvisionDriver;
use ImranDevBd\AttendanceHub\Drivers\SupremaDriver;
use ImranDevBd\AttendanceHub\Drivers\VirtualDriver;
use ImranDevBd\AttendanceHub\Drivers\WebhookBridgeDriver;
use ImranDevBd\AttendanceHub\Drivers\ZKTecoDriver;
use ImranDevBd\AttendanceHub\DTOs\AttendanceShift;
use ImranDevBd\AttendanceHub\Events\DeviceCameOnline;
use ImranDevBd\AttendanceHub\Events\DeviceWentOffline;
use ImranDevBd\AttendanceHub\Events\SyncFailed;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Support\AttendanceCalculator;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;
use ImranDevBd\AttendanceHub\Support\DeviceAutoDetector;
use ImranDevBd\AttendanceHub\Support\DeviceCatalog;
use ImranDevBd\AttendanceHub\Support\DeviceTemplateVault;
use ImranDevBd\AttendanceHub\Support\NetworkScanner;

class AttendanceHubManager
{
    /**
     * Custom driver resolvers.
     *
     * @var array<string, Closure>
     */
    /**
     * Active fake driver instance when in testing/simulation mode.
     */
    protected ?FakeDeviceDriver $fakeDriver = null;

    public function __construct(
        protected Application $app,
        protected AttendanceNormalizer $normalizer = new AttendanceNormalizer(),
        protected DeviceAutoDetector $autoDetector = new DeviceAutoDetector(),
        protected NetworkScanner $networkScanner = new NetworkScanner(),
        protected ?AttendanceCalculator $calculator = null,
        protected ?DeviceTemplateVault $vault = null
    ) {
        $this->calculator = $calculator ?? new AttendanceCalculator();
        $this->vault = $vault ?? new DeviceTemplateVault();
    }

    /**
     * Swap the manager into fake mode with a simulated device driver.
     */
    public function fake(array $initialPunches = []): FakeDeviceDriver
    {
        $this->fakeDriver = new FakeDeviceDriver($initialPunches);

        return $this->fakeDriver;
    }

    /**
     * Check if currently operating in simulated fake mode.
     */
    public function isFake(): bool
    {
        return $this->fakeDriver !== null;
    }

    /**
     * Get a driver instance by provider name.
     */
    public function provider(?string $provider = null): DeviceDriverInterface
    {
        if ($this->fakeDriver !== null) {
            return $this->fakeDriver;
        }

        $provider = $provider ?: config('attendance-hub.default_provider', 'zkteco');

        if (isset($this->customDrivers[$provider])) {
            return call_user_func($this->customDrivers[$provider], $this->app);
        }

        $key = strtolower($provider);

        // Check if provider matches or is a recognized catalog model
        $driverKey = match ($key) {
            'zkteco', 'zk', 'fingertec', 'granding', 'deli' => 'zkteco',
            'adms', 'iclock', 'essl', 'anviz', 'realtime' => 'adms',
            'hikvision', 'isapi' => 'hikvision',
            'suprema', 'biostar' => 'suprema',
            'dahua' => 'dahua',
            'virdi', 'soyal', 'webhook_bridge', 'bridge', 'wiegand' => 'webhook_bridge',
            'virtual', 'mobile', 'gps', 'qr' => 'virtual',
            'fake', 'mock', 'simulation' => 'fake',
            default => DeviceCatalog::resolveDriver($provider),
        };

        return match ($driverKey) {
            'zkteco' => new ZKTecoDriver(),
            'adms' => new AdmsPushDriver(),
            'hikvision' => new HikvisionDriver(),
            'suprema' => new SupremaDriver(),
            'dahua' => new DahuaDriver(),
            'virtual' => new VirtualDriver(),
            'webhook_bridge' => new WebhookBridgeDriver(),
            'fake' => new FakeDeviceDriver(),
            default => throw new InvalidArgumentException("Unsupported attendance driver [{$provider}]."),
        };
    }

    /**
     * Resolve a driver directly for a specific hardware model name.
     */
    public function model(string $modelName): DeviceDriverInterface
    {
        $driverKey = DeviceCatalog::resolveDriver($modelName);

        return $this->provider($driverKey);
    }

    /**
     * Access the device catalog helper.
     */
    public function catalog(): DeviceCatalog
    {
        return new DeviceCatalog();
    }

    /**
     * Register a custom driver creator Closure.
     */
    public function extend(string $driver, Closure $callback): self
    {
        $this->customDrivers[$driver] = $callback;
        return $this;
    }

    /**
     * Auto-detect device provider from IP and return connected driver.
     */
    public function autoDetect(string $ip, int $timeout = 3): ?DeviceDriverInterface
    {
        if ($this->fakeDriver !== null) {
            $this->fakeDriver->connect(new DeviceConnection(ip: $ip, port: 4370));
            return $this->fakeDriver;
        }

        $detected = $this->autoDetector->detect($ip, $timeout);

        if (!$detected) {
            return null;
        }

        $driver = $this->provider($detected['provider']);
        $driver->connect(new DeviceConnection(
            ip: $ip,
            port: $detected['details']['port'] ?? 4370,
            protocol: $detected['details']['protocol'] ?? 'tcp',
            timeout: $timeout
        ));

        return $driver;
    }

    /**
     * Connect to a registered device model and return driver.
     */
    public function device(AttendanceDevice|int|string $device): DeviceDriverInterface
    {
        if (!$device instanceof AttendanceDevice) {
            $device = AttendanceDevice::findOrFail($device);
        }

        $driver = $this->provider($device->provider);
        $connection = $device->toConnection();
        $driver->connect($connection);

        return $driver;
    }

    /**
     * Pull and normalize attendance logs for a device.
     *
     * @return Collection<int, \ImranDevBd\AttendanceHub\Models\AttendanceLog>
     */
    public function sync(AttendanceDevice|int|string $device, ?CarbonInterface $since = null): Collection
    {
        if (!$device instanceof AttendanceDevice) {
            $device = AttendanceDevice::findOrFail($device);
        }

        $driver = $this->device($device);

        try {
            if (!$driver->ping()) {
                if ($device->isOnline()) {
                    $device->markOffline();
                    event(new DeviceWentOffline($device, 'Device unreachable during sync'));
                }
                return collect();
            }

            if (!$device->isOnline()) {
                $device->markOnline();
                event(new DeviceCameOnline($device));
            }

            $effectiveSince = $since ?? ($device->last_synced_at ? $device->last_synced_at->subHours(2) : null);
            $punches = $driver->pullAttendanceLogs($effectiveSince);

            $savedLogs = $this->normalizer->recordMany($punches, $device);

            $device->update([
                'last_synced_at' => now(),
                'last_seen_at' => now(),
            ]);

            // Clear device logs if auto_clear_logs is enabled
            if ($device->auto_clear_logs && $savedLogs->isNotEmpty()) {
                $driver->clearLogs();
            }

            $driver->disconnect();

            return $savedLogs;
        } catch (\Throwable $e) {
            event(new SyncFailed($device, $e));
            $driver->disconnect();
            throw $e;
        }
    }

    /**
     * Get the normalizer instance.
     */
    public function normalizer(): AttendanceNormalizer
    {
        return $this->normalizer;
    }

    /**
     * Get the auto-detector instance.
     */
    public function detector(): DeviceAutoDetector
    {
        return $this->autoDetector;
    }

    /**
     * Get the network scanner instance.
     */
    public function scanner(): NetworkScanner
    {
        return $this->networkScanner;
    }

    /**
     * Get the attendance shift calculator instance.
     */
    public function calculator(?AttendanceShift $shift = null): AttendanceCalculator
    {
        if ($shift !== null) {
            return new AttendanceCalculator($shift);
        }

        return $this->calculator;
    }

    /**
     * Get the biometric template vault instance.
     */
    public function vault(): DeviceTemplateVault
    {
        return $this->vault;
    }
}
