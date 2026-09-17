<?php

namespace ImranDevBd\AttendanceHub\Support;

class DeviceCatalog
{
    /**
     * Master catalog of supported device brands and models.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static ?array $catalog = null;

    /**
     * Get all catalog entries.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (static::$catalog === null) {
            static::$catalog = static::buildCatalog();

            // Load any user-configured custom models from config/attendance-hub.php
            $customModels = config('attendance-hub.custom_models', []);
            foreach ($customModels as $model => $attributes) {
                static::register(is_string($model) ? $model : ($attributes['model'] ?? 'Unknown'), $attributes);
            }
        }

        return static::$catalog;
    }

    /**
     * Dynamically register a custom hardware model into the catalog at runtime.
     *
     * @param array<string, mixed> $attributes
     */
    public static function register(string $model, array $attributes = []): void
    {
        if (static::$catalog === null) {
            static::$catalog = static::buildCatalog();
        }

        $provider = $attributes['provider'] ?? 'Custom';
        $key = strtolower(trim($provider)) . '_' . $model;

        static::$catalog[$key] = array_merge([
            'provider' => $provider,
            'model' => $model,
            'series' => $attributes['series'] ?? 'Custom Series',
            'fp' => $attributes['fp'] ?? false,
            'face' => $attributes['face'] ?? false,
            'palm' => $attributes['palm'] ?? false,
            'rfid' => $attributes['rfid'] ?? false,
            'tcp' => $attributes['tcp'] ?? true,
            'adms' => $attributes['adms'] ?? false,
            'cloud' => $attributes['cloud'] ?? false,
            'port' => $attributes['port'] ?? 4370,
            'driver' => $attributes['driver'] ?? 'zkteco',
            'difficulty' => $attributes['difficulty'] ?? 'Custom',
            'bd_market' => $attributes['bd_market'] ?? 'Custom',
            'notes' => $attributes['notes'] ?? 'User-registered custom hardware model.',
        ], $attributes);
    }

    /**
     * Register multiple custom models at once.
     *
     * @param array<string|int, array<string, mixed>> $models
     */
    public static function registerMany(array $models): void
    {
        foreach ($models as $model => $attributes) {
            $name = is_string($model) ? $model : ($attributes['model'] ?? 'Custom');
            static::register($name, $attributes);
        }
    }

    /**
     * Reset the catalog cache (primarily used in tests).
     */
    public static function reset(): void
    {
        static::$catalog = null;
    }

    /**
     * Get list of all supported provider brands.
     *
     * @return array<int, string>
     */
    public static function providers(): array
    {
        return [
            'zkteco' => 'ZKTeco',
            'hikvision' => 'Hikvision',
            'anviz' => 'Anviz',
            'fingertec' => 'FingerTec',
            'essl' => 'eSSL',
            'virdi' => 'VIRDI',
            'suprema' => 'Suprema',
            'deli' => 'Deli',
            'granding' => 'Granding',
            'soyal' => 'Soyal',
        ];
    }

    /**
     * Get all models belonging to a specific provider brand.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function forProvider(string $provider): array
    {
        $provider = strtolower(trim($provider));
        $all = static::all();

        return array_filter($all, function ($entry) use ($provider) {
            return strtolower($entry['provider']) === $provider;
        });
    }

    /**
     * Find a specific model in the catalog (case-insensitive, normalized).
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $model): ?array
    {
        $all = static::all();
        $normalized = static::normalizeKey($model);

        foreach ($all as $key => $entry) {
            if (static::normalizeKey($key) === $normalized || static::normalizeKey($entry['model']) === $normalized) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Resolve the recommended driver for a given model or provider.
     */
    public static function resolveDriver(string $modelOrProvider): string
    {
        $entry = static::find($modelOrProvider);
        if ($entry !== null) {
            return $entry['driver'];
        }

        $provider = strtolower(trim($modelOrProvider));

        return match ($provider) {
            'zkteco', 'zk', 'fingertec', 'granding', 'deli' => 'zkteco',
            'adms', 'iclock', 'essl', 'anviz', 'realtime' => 'adms',
            'hikvision', 'isapi' => 'hikvision',
            'suprema', 'biostar' => 'suprema',
            'dahua' => 'dahua',
            'virdi', 'soyal', 'webhook_bridge', 'bridge', 'wiegand' => 'webhook_bridge',
            'virtual', 'mobile', 'gps', 'qr' => 'virtual',
            'fake', 'mock', 'simulation' => 'fake',
            default => 'zkteco',
        };
    }

    /**
     * Search models by keyword across brand, model, and capabilities.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function search(string $query): array
    {
        $query = strtolower(trim($query));
        if ($query === '') {
            return static::all();
        }

        $all = static::all();

        return array_filter($all, function ($entry) use ($query) {
            return str_contains(strtolower($entry['model']), $query)
                || str_contains(strtolower($entry['provider']), $query)
                || str_contains(strtolower($entry['series'] ?? ''), $query)
                || str_contains(strtolower($entry['driver']), $query);
        });
    }

    /**
     * Get summary counts per provider.
     *
     * @return array<string, int>
     */
    public static function summary(): array
    {
        $counts = [];
        foreach (static::all() as $entry) {
            $p = $entry['provider'];
            $counts[$p] = ($counts[$p] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Normalize model string for lookup.
     */
    protected static function normalizeKey(string $key): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $key));
    }

    /**
     * Build the catalog data structure (150+ models across 10 brands).
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function buildCatalog(): array
    {
        $items = [];

        // -------------------------------------------------------------
        // 1. ZKTeco — 20 Models
        // -------------------------------------------------------------
        $zkteco = [
            'UA860' => ['series' => 'UA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'TCP/IP, Wi-Fi, USB, ADMS push built-in.'],
            'UA760' => ['series' => 'UA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Color screen fingerprint & RFID terminal.'],
            'UA660' => ['series' => 'UA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'BioID fingerprint sensor with ADMS.'],
            'UA300' => ['series' => 'UA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Widely deployed classic biometric terminal.'],
            'UA200' => ['series' => 'UA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Standard standalone TCP/IP fingerprint reader.'],
            'MB20' => ['series' => 'MB Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Multi-biometric fingerprint + visible face reader.'],
            'MB30' => ['series' => 'MB Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Hybrid biometric time attendance with ADMS.'],
            'MB40' => ['series' => 'MB Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Dual fingerprint and facial verification.'],
            'MB360' => ['series' => 'MB Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Enterprise hybrid face + fingerprint device.'],
            'MB560-VL' => ['series' => 'Visible Light', 'fp' => true, 'face' => true, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Visible Light facial recognition with anti-spoofing.'],
            'MB460' => ['series' => 'MB Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Face + fingerprint + card reader.'],
            'K14' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Ultra-popular budget model in South Asia.'],
            'K40' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Fingerprint terminal with built-in battery backup.'],
            'K50' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Color display biometric terminal with SSR reports.'],
            'K60' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Standard network time attendance terminal.'],
            'iClock 260' => ['series' => 'iClock Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Enterprise ADMS cloud push fingerprint terminal.'],
            'iClock 360' => ['series' => 'iClock Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Heavy duty factory & corporate ADMS terminal.'],
            'iClock 700' => ['series' => 'iClock Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'High-capacity camera + fingerprint ADMS device.'],
            'SpeedFace-V5L' => ['series' => 'SpeedFace', 'fp' => true, 'face' => true, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Fast touchless Visible Light face & palm reader.'],
            'SpeedFace-V5L [TD]' => ['series' => 'SpeedFace', 'fp' => true, 'face' => true, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'SpeedFace with thermal body temperature detection.'],
        ];

        foreach ($zkteco as $model => $meta) {
            $items["zkteco_{$model}"] = array_merge(['provider' => 'ZKTeco', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 2. Hikvision — 15 Models
        // -------------------------------------------------------------
        $hikvision = [
            'DS-K1A8503' => ['series' => 'DS-K1A Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Standalone fingerprint time attendance terminal.'],
            'DS-K1A8503EF' => ['series' => 'DS-K1A Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'EM Card + optical fingerprint sensor with ISAPI.'],
            'DS-K1A8503MF' => ['series' => 'DS-K1A Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Mifare card + fingerprint ISAPI terminal.'],
            'DS-K1T341AMF' => ['series' => 'DS-K1T Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Face recognition terminal with deep learning.'],
            'DS-K1T341CMF' => ['series' => 'DS-K1T Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => '4.3-inch touch screen face recognition terminal.'],
            'DS-K1T343MFWX' => ['series' => 'DS-K1T Series', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Face recognition terminal with Wi-Fi & Hik-Connect.'],
            'DS-K1T343MWX' => ['series' => 'DS-K1T Series', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Compact face recognition terminal with Wi-Fi.'],
            'DS-K1T671MF' => ['series' => 'DS-K1T Pro', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => '7-inch enterprise face & fingerprint terminal.'],
            'DS-K1T671TM' => ['series' => 'DS-K1T Pro', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Thermographic temperature screening face terminal.'],
            'DS-K1T671M' => ['series' => 'DS-K1T Pro', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => '7-inch IPS touchscreen face terminal.'],
            'DS-K1T320M' => ['series' => 'DS-K1T Value', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Value series face recognition access control.'],
            'DS-K1T804' => ['series' => 'DS-K1T804', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Optical fingerprint & RFID card terminal with LCD.'],
            'DS-K1T804AMF' => ['series' => 'DS-K1T804', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Mifare + fingerprint standalone terminal.'],
            'DS-K1T805' => ['series' => 'DS-K1T805', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Vandal-resistant outdoor fingerprint terminal.'],
            'DS-K1T606' => ['series' => 'DS-K1T606', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 80, 'driver' => 'hikvision', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Wall-mounted face and fingerprint reader.'],
        ];

        foreach ($hikvision as $model => $meta) {
            $items["hikvision_{$model}"] = array_merge(['provider' => 'Hikvision', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 3. Anviz — 21 Models
        // -------------------------------------------------------------
        $anviz = [
            'C2 Pro' => ['series' => 'C Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Dual-core processor with CrossChex Cloud & B-comm.'],
            'A350' => ['series' => 'A Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Cloud time attendance with Wi-Fi & WebServer.'],
            'A350C' => ['series' => 'A Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'A350 with RFID card reader.'],
            'W1' => ['series' => 'W Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Color screen fingerprint with touch keypad.'],
            'W1 Pro' => ['series' => 'W Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Upgraded touch keypad fingerprint terminal.'],
            'W1C Pro' => ['series' => 'W Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Card + fingerprint with Cloud sync.'],
            'W2' => ['series' => 'W Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Color screen biometric time attendance terminal.'],
            'W2 Pro' => ['series' => 'W Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Wi-Fi enabled Linux based terminal.'],
            'VF30 Pro' => ['series' => 'VF Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'PoE powered access control & attendance device.'],
            'EP300' => ['series' => 'EP Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Classic desktop/wallmount fingerprint reader.'],
            'EP300 Pro' => ['series' => 'EP Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Rechargeable battery + Wi-Fi cloud terminal.'],
            'EP30' => ['series' => 'EP Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Compact entry-level fingerprint machine.'],
            'CX2' => ['series' => 'CX Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Linux-based CrossChex Cloud terminal.'],
            'CX2 Lite' => ['series' => 'CX Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Cost-effective smart cloud time attendance.'],
            'CX7' => ['series' => 'CX Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => '7-inch touchscreen biometric station.'],
            'FaceDeep 3' => ['series' => 'FaceDeep', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'AI dual-camera face recognition terminal.'],
            'FaceDeep 3 IRT' => ['series' => 'FaceDeep', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'FaceDeep 3 with infrared temperature detection.'],
            'FaceDeep 5' => ['series' => 'FaceDeep', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => '50,000 capacity enterprise face terminal.'],
            'FaceDeep 5 IRT' => ['series' => 'FaceDeep', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Enterprise thermal face terminal.'],
            'FacePass 7 Pro' => ['series' => 'FacePass', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Smart IR facial recognition with live detection.'],
            'FacePass 7 Pro IRT' => ['series' => 'FacePass', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 5010, 'driver' => 'adms', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'FacePass 7 Pro with thermal camera sensor.'],
        ];

        foreach ($anviz as $model => $meta) {
            $items["anviz_{$model}"] = array_merge(['provider' => 'Anviz', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 4. FingerTec — 18 Models (OEM ZK Socket / Push Protocol)
        // -------------------------------------------------------------
        $fingertec = [
            'TA300' => ['series' => 'TA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'TCP/IP and USB fingerprint attendance terminal.'],
            'TA500' => ['series' => 'TA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Very popular office fingerprint system.'],
            'TA700W' => ['series' => 'TA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Wi-Fi enabled fingerprint attendance machine.'],
            'TA100C' => ['series' => 'TA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Color screen time recorder.'],
            'TA200 Plus' => ['series' => 'TA Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Multimedia fingerprint & card terminal.'],
            'AC100C' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Access control & time attendance machine.'],
            'AC900' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Rugged door access control fingerprint device.'],
            'R2' => ['series' => 'R Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Door access control and time attendance.'],
            'R3' => ['series' => 'R Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Upgraded optical sensor master reader.'],
            'H2i' => ['series' => 'H Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Compact master access control & punch recorder.'],
            'Q2i' => ['series' => 'Q Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Color multimedia terminal with voice prompts.'],
            'Kadex' => ['series' => 'Kadex', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Dedicated RFID card time attendance.'],
            'm-Kadex' => ['series' => 'Kadex', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Slim weather-resistant card terminal.'],
            'Face ID 2' => ['series' => 'Face ID', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Face, fingerprint & password multi-biometric.'],
            'Face ID 3' => ['series' => 'Face ID', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Dedicated touchless face recognition.'],
            'Face ID 4' => ['series' => 'Face ID', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'High accuracy facial verification terminal.'],
            'Face ID 4d' => ['series' => 'Face ID', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Door access oriented facial scanner.'],
            'Face ID X' => ['series' => 'Face ID', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Flagship fast visible face and fingerprint reader.'],
        ];

        foreach ($fingertec as $model => $meta) {
            $items["fingertec_{$model}"] = array_merge(['provider' => 'FingerTec', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 5. eSSL — 22 Models (Major South Asia Market Brand)
        // -------------------------------------------------------------
        $essl = [
            'X990' => ['series' => 'X Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Standalone fingerprint with push data protocol.'],
            'iClock990' => ['series' => 'iClock', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Camera + fingerprint high capacity terminal.'],
            'VEGA+W+POE' => ['series' => 'VEGA', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'PoE powered fingerprint time attendance.'],
            'F18' => ['series' => 'F Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Classic access control and attendance terminal.'],
            'F22+ID+WIFI' => ['series' => 'F Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Ultra-thin touch keypad biometric reader.'],
            'SF100' => ['series' => 'SF Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'IP based fingerprint terminal.'],
            'K30PRO' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Fingerprint with in-built battery backup.'],
            'FR1200' => ['series' => 'FR Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'webhook_bridge', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'RS485 slave fingerprint reader.'],
            'X7' => ['series' => 'X Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'webhook_bridge', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Keypad and fingerprint standalone lock reader.'],
            'P160' => ['series' => 'Palm Series', 'fp' => true, 'face' => false, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Palm and fingerprint multi-biometric.'],
            'Silk-FP-101TA' => ['series' => 'Silk Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'SilkID sensor for wet, rough, dry fingers.'],
            'WL20' => ['series' => 'WL Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => false, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Wi-Fi fingerprint time attendance terminal.'],
            'Aiface Vesta+POE' => ['series' => 'Aiface', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'AI face recognition with visible light technology.'],
            'AIFACE SUN+POE' => ['series' => 'Aiface', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Sunlight readable AI face & fingerprint reader.'],
            'AIFACE VIKTOR' => ['series' => 'Aiface', 'fp' => true, 'face' => true, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Face, palm and fingerprint attendance terminal.'],
            'EFACE990' => ['series' => 'EFace', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Touch screen multi-biometric time attendance.'],
            'UFACE302' => ['series' => 'UFace', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Multi-biometric facial identification device.'],
            'MB160' => ['series' => 'MB Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Fingerprint & face recognition terminal.'],
            'SFace900' => ['series' => 'SFace', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Semi-outdoor visible light face & fingerprint device.'],
            'K90 Pro' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Standard biometric fingerprint terminal.'],
            'K21 Pro' => ['series' => 'K Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'High speed verification fingerprint reader.'],
            'eSSL9500' => ['series' => 'Enterprise', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'adms', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Large enterprise capacity terminal.'],
        ];

        foreach ($essl as $model => $meta) {
            $items["essl_{$model}"] = array_merge(['provider' => 'eSSL', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 6. VIRDI — 12 Models
        // -------------------------------------------------------------
        $virdi = [
            'AC-5000' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'IP65 waterproof fingerprint terminal with PoE.'],
            'AC-6000' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Color touchscreen camera fingerprint terminal.'],
            'AC-2100' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'IPX3 rated access control & time attendance.'],
            'AC-2200' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Smart card and fingerprint terminal with Bluetooth.'],
            'AC-4000' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Enterprise fingerprint voice prompt terminal.'],
            'AC-5100' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Graphic LCD outdoor fingerprint terminal.'],
            'AC-7000' => ['series' => 'AC Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Dual face and fingerprint luxury terminal.'],
            'AC-2100 Plus' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Fake fingerprint detection with IP65 rating.'],
            'AC-5000 Plus' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Heavy duty factory access control terminal.'],
            'UBio-X Face' => ['series' => 'UBio Series', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'High speed walk-through facial recognition.'],
            'UBio-X Iris' => ['series' => 'UBio Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'High', 'bd_market' => 'Low', 'notes' => 'Military grade iris and fingerprint scanner.'],
            'AC-2000' => ['series' => 'AC Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 9870, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Smartphone key & fingerprint reader.'],
        ];

        foreach ($virdi as $model => $meta) {
            $items["virdi_{$model}"] = array_merge(['provider' => 'VIRDI', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 7. Suprema — 18 Models (Enterprise BioStar 2 REST API)
        // -------------------------------------------------------------
        $suprema = [
            'BioStation 2' => ['series' => 'BioStation', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Flagship high performance fingerprint terminal.'],
            'BioStation 3' => ['series' => 'BioStation', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'AI facial recognition with QR & mobile credentials.'],
            'BioStation 2a' => ['series' => 'BioStation', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Deep-learning based high capacity fingerprint reader.'],
            'BioStation A2' => ['series' => 'BioStation', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Quad-core CPU with OPOS optical sensor.'],
            'BioStation L2' => ['series' => 'BioStation', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Cost-effective high accuracy fingerprint terminal.'],
            'BioEntry W2' => ['series' => 'BioEntry', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'IP67/IK09 vandal-proof outdoor fingerprint terminal.'],
            'BioEntry W3' => ['series' => 'BioEntry', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Next-gen AI face recognition reader.'],
            'BioEntry P2' => ['series' => 'BioEntry', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Mullion-type slim indoor fingerprint terminal.'],
            'BioEntry R2' => ['series' => 'BioEntry', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'RS-485 slave fingerprint reader.'],
            'BioLite N2' => ['series' => 'BioLite', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Outdoor IP67 keypad fingerprint terminal.'],
            'FaceLite' => ['series' => 'FaceStation', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Compact touchless facial recognition reader.'],
            'FaceStation 2' => ['series' => 'FaceStation', 'fp' => false, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Ultra performance facial recognition terminal.'],
            'FaceStation F2' => ['series' => 'FaceStation', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Fusion multi-modal face and fingerprint reader.'],
            'XPass 2' => ['series' => 'XPass', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Outdoor RFID smart card reader.'],
            'XPass D2' => ['series' => 'XPass', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Mullion-type RFID card reader.'],
            'X-Station 2' => ['series' => 'X-Station', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Versatile QR code & RFID intelligent terminal.'],
            'CoreStation' => ['series' => 'Controller', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => true, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Intelligent biometric door controller hub.'],
            'BioEntry W' => ['series' => 'BioEntry', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 443, 'driver' => 'suprema', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Vandal resistant PoE outdoor terminal.'],
        ];

        foreach ($suprema as $model => $meta) {
            $items["suprema_{$model}"] = array_merge(['provider' => 'Suprema', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 8. Deli — 10 Models
        // -------------------------------------------------------------
        $deli = [
            'E3960' => ['series' => 'E Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'USB/LAN fingerprint attendance machine.'],
            'E3765' => ['series' => 'E Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => false, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Color LCD fingerprint machine.'],
            'E13750' => ['series' => 'E Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Network biometric time attendance machine.'],
            'ES152' => ['series' => 'ES Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Smart biometric terminal with Wi-Fi.'],
            'ES151' => ['series' => 'ES Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Voice prompt fingerprint clock-in machine.'],
            'ES161' => ['series' => 'ES Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Face and fingerprint hybrid machine.'],
            'ES171' => ['series' => 'ES Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Infrared face and fingerprint terminal.'],
            'ES172' => ['series' => 'ES Series', 'fp' => true, 'face' => true, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Face, palm vein, fingerprint 100K capacity.'],
            'E3747' => ['series' => 'E Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => false, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Budget fingerprint attendance recorder.'],
            'E3758' => ['series' => 'E Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Network time attendance machine with ID card.'],
        ];

        foreach ($deli as $model => $meta) {
            $items["deli_{$model}"] = array_merge(['provider' => 'Deli', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 9. Granding — 15 Models (ZK OEM Sockets & ADMS Push)
        // -------------------------------------------------------------
        $granding = [
            'GT100' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'High speed fingerprint time attendance.'],
            'GT110' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Compact biometric attendance terminal.'],
            'GT2100' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Multimedia fingerprint & card terminal.'],
            'GT2100F' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Optical fingerprint with camera snapshot.'],
            'GT300' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Wireless GPRS/Wi-Fi fingerprint terminal.'],
            'GT800' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Palm and fingerprint biometric terminal with battery.'],
            'GT-1000' => ['series' => 'GT Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'High security biometric recorder.'],
            'T5' => ['series' => 'T Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Slim fingerprint access control terminal.'],
            'T6' => ['series' => 'T Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => false, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Door access biometric unit.'],
            'FA1' => ['series' => 'FA Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Very High', 'notes' => 'Popular hybrid face and fingerprint terminal.'],
            'FA2' => ['series' => 'FA Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Dual infrared camera facial verification.'],
            'FA1-H' => ['series' => 'FA Series', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'High capacity face and RFID terminal.'],
            'BioStation' => ['series' => 'Granding Bio', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'Granding enterprise attendance station.'],
            'FacePro' => ['series' => 'FacePro', 'fp' => true, 'face' => true, 'palm' => true, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'High', 'notes' => 'Visible light touchless face and palm recognition.'],
            'BioFace' => ['series' => 'BioFace', 'fp' => true, 'face' => true, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => true, 'cloud' => true, 'port' => 4370, 'driver' => 'zkteco', 'difficulty' => 'Easy', 'bd_market' => 'Medium', 'notes' => 'High accuracy facial verification unit.'],
        ];

        foreach ($granding as $model => $meta) {
            $items["granding_{$model}"] = array_merge(['provider' => 'Granding', 'model' => $model], $meta);
        }

        // -------------------------------------------------------------
        // 10. Soyal — 15 Models (Access Control & Wiegand Panels)
        // -------------------------------------------------------------
        $soyal = [
            'AR-837EF' => ['series' => 'AR Series', 'fp' => true, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'LCD fingerprint access controller with TCP/IP.'],
            'AR-888' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'European-style smart card proximity reader.'],
            'AR-888UL' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Wiegand illuminated RFID reader.'],
            'AR-725E' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Touch-panel access controller with TCP/IP.'],
            'AR-725H' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'RS-485 touch keypad controller.'],
            'AR-829E' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Graphic LCD display TCP/IP controller.'],
            'AR-331' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Waterproof metal casing proximity reader.'],
            'AR-321CM' => ['series' => 'Converter', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => false, 'tcp' => true, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'RS-485 to TCP/IP Ethernet converter.'],
            'AR-888U' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'US-style flush mount proximity reader.'],
            'AR-725' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Illuminated touch keypad reader.'],
            'AR-721H' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Very High', 'notes' => 'Most common Taiwanese RS-485 card controller.'],
            'AR-757H' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Metal keypad durable controller.'],
            'AR-829' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'LCD standalone access controller.'],
            'AR-888S' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'Medium', 'notes' => 'Square touch keypad proximity device.'],
            'AR-727H' => ['series' => 'AR Series', 'fp' => false, 'face' => false, 'palm' => false, 'rfid' => true, 'tcp' => false, 'adms' => false, 'cloud' => false, 'port' => 1621, 'driver' => 'webhook_bridge', 'difficulty' => 'Medium', 'bd_market' => 'High', 'notes' => 'Backlit LCD master access controller.'],
        ];

        foreach ($soyal as $model => $meta) {
            $items["soyal_{$model}"] = array_merge(['provider' => 'Soyal', 'model' => $model], $meta);
        }

        return $items;
    }
}
