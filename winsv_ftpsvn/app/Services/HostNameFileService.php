<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class HostNameFileService
{
    private const FILE = 'host-mapped-names.list.json';

    private static function read(): array
    {
        if (!Storage::exists(self::FILE)) {
            Storage::put(self::FILE, '{}');
        }

        return json_decode(
            Storage::get(self::FILE),
            true
        ) ?? [];
    }

    private static function write(array $data): void
    {
        Storage::put(
            self::FILE,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function all(): array
    {
        return self::read();
    }

    public static function get(string $agentId): ?string
    {
        return self::read()[$agentId] ?? null;
    }

    public static function set(string $agentId, string $name): void
    {
        $data = self::read();
        $data[$agentId] = $name;
        self::write($data);
    }
}
