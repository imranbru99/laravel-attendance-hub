<?php

namespace ImranDevBd\AttendanceHub\Support;

use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\DeviceBiometricTemplate;

class DeviceTemplateVault
{
    /**
     * Pull and backup user biometric templates from a physical device into the vault.
     */
    public function backupFromDevice(AttendanceDevice|int|string $device): int
    {
        if (!$device instanceof AttendanceDevice) {
            $device = AttendanceDevice::findOrFail($device);
        }

        $driver = AttendanceHub::device($device);
        $users = $driver->pullUsers();
        $driver->disconnect();

        $savedCount = 0;

        foreach ($users as $user) {
            // Save fingerprint templates
            foreach ($user->fingerprints as $index => $templateData) {
                DeviceBiometricTemplate::updateOrCreate(
                    [
                        'device_user_id' => $user->userId,
                        'template_type' => 'fingerprint',
                        'finger_index' => (int) $index,
                    ],
                    [
                        'device_id' => $device->id,
                        'employee_id' => $user->userId,
                        'template_data' => is_string($templateData) ? base64_encode($templateData) : json_encode($templateData),
                        'version' => 'ZK-Standard',
                    ]
                );
                $savedCount++;
            }

            // Save face template if available
            if (!empty($user->faceTemplate)) {
                DeviceBiometricTemplate::updateOrCreate(
                    [
                        'device_user_id' => $user->userId,
                        'template_type' => 'face',
                        'finger_index' => 0,
                    ],
                    [
                        'device_id' => $device->id,
                        'employee_id' => $user->userId,
                        'template_data' => base64_encode($user->faceTemplate),
                        'version' => 'Face-Standard',
                    ]
                );
                $savedCount++;
            }
        }

        return $savedCount;
    }

    /**
     * Restore and push stored vault templates to a target device.
     */
    public function restoreToDevice(AttendanceDevice|int|string $targetDevice, ?array $userIds = null): int
    {
        if (!$targetDevice instanceof AttendanceDevice) {
            $targetDevice = AttendanceDevice::findOrFail($targetDevice);
        }

        $query = DeviceBiometricTemplate::query();
        if (!empty($userIds)) {
            $query->whereIn('device_user_id', $userIds);
        }

        $templates = $query->get();
        if ($templates->isEmpty()) {
            return 0;
        }

        $driver = AttendanceHub::device($targetDevice);
        $pushedCount = 0;

        // Group templates by user
        $grouped = $templates->groupBy('device_user_id');

        foreach ($grouped as $userId => $userTemplates) {
            $fingerprints = [];
            $faceTemplate = null;

            foreach ($userTemplates as $tmpl) {
                if ($tmpl->template_type === 'fingerprint') {
                    $fingerprints[$tmpl->finger_index] = base64_decode($tmpl->template_data);
                } elseif ($tmpl->template_type === 'face') {
                    $faceTemplate = base64_decode($tmpl->template_data);
                }
            }

            $user = new \ImranDevBd\AttendanceHub\DTOs\DeviceUser(
                uid: (string) $userId,
                userId: (string) $userId,
                name: "User {$userId}",
                fingerprints: $fingerprints,
                faceTemplate: $faceTemplate
            );

            if ($driver->pushUser($user)) {
                $pushedCount++;
            }
        }

        $driver->disconnect();

        return $pushedCount;
    }

    /**
     * Get all stored biometric templates for a user.
     *
     * @return Collection<int, DeviceBiometricTemplate>
     */
    public function getTemplatesForUser(string $deviceUserId): Collection
    {
        return DeviceBiometricTemplate::where('device_user_id', $deviceUserId)->get();
    }
}
