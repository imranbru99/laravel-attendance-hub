<?php

namespace ImranDevBd\AttendanceHub\DTOs;

class DeviceConnection
{
    public function __construct(
        public readonly ?string $ip = null,
        public readonly int $port = 4370,
        public readonly ?string $protocol = 'tcp', // 'tcp', 'udp', 'http', 'https'
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly ?string $token = null,
        public readonly ?string $serialNumber = null,
        public readonly int $timeout = 5,
        public readonly array $options = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ip: $data['ip'] ?? null,
            port: (int) ($data['port'] ?? 4370),
            protocol: $data['protocol'] ?? 'tcp',
            username: $data['username'] ?? null,
            password: $data['password'] ?? null,
            token: $data['token'] ?? null,
            serialNumber: $data['serial'] ?? $data['serial_number'] ?? null,
            timeout: (int) ($data['timeout'] ?? 5),
            options: $data['options'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'port' => $this->port,
            'protocol' => $this->protocol,
            'username' => $this->username,
            'password' => $this->password,
            'token' => $this->token,
            'serial' => $this->serialNumber,
            'timeout' => $this->timeout,
            'options' => $this->options,
        ];
    }
}
