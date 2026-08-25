<?php

namespace App\Contracts\Repositories;

use App\Enums\AuditType;
use App\Models\AuditLog;
use App\Models\User;

interface AuditLogRepositoryInterface
{
    public function record(User $user, AuditType $type, string $description, array $properties = []): AuditLog;
}
