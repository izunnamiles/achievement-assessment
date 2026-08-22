<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Attributes\Boot;
use Illuminate\Support\Str;

trait HasUuid
{
    #[Boot]
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            $model->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
