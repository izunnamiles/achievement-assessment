<?php

namespace App\Repositories;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Enums\AuditType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Date;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function record(User $user, AuditType $type, string $description, array $properties = []): AuditLog
    {
        return AuditLog::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'description' => $description,
            'properties' => $properties,
            'created_at' => Date::now(),
        ]);
    }
}
