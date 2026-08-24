<?php

namespace App\Contracts\Repositories;

interface SystemSettingRepositoryInterface
{
    /**
     * Fetch a setting's value, cast to match the type of $default when given.
     */
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;
}
