<?php

namespace ImranDevBd\AttendanceHub\DTOs;

class DeviceUser
{
    public function __construct(
        public readonly string $uid,              // internal device index/uid
        public readonly string $userId,           // display/badge ID or PIN
        public readonly string $name,
        public readonly int $role = 0,            // 0=normal user, 14=admin
        public readonly ?string $password = null,
        public readonly ?string $card = null,
        public readonly array $fingerprints = [], // templates
        public readonly ?string $faceTemplate = null,
        public readonly ?string $photoUrl = null,
        public readonly bool $enabled = true,
        public readonly array $rawPayload = []
    ) {}

    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'user_id' => $this->userId,
            'name' => $this->name,
            'role' => $this->role,
            'password' => $this->password,
            'card' => $this->card,
            'fingerprints_count' => count($this->fingerprints),
            'has_face' => !empty($this->faceTemplate),
            'enabled' => $this->enabled,
        ];
    }
}
