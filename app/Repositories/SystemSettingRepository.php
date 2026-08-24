<?php

namespace App\Repositories;

use App\Contracts\Repositories\SystemSettingRepositoryInterface;
use App\Models\SystemSetting;

class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        $value = SystemSetting::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return match (true) {
            is_int($default) => (int) $value,
            is_float($default) => (float) $value,
            is_bool($default) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value],
        );
    }
}
