<?php

namespace Database\Factories;

use App\Enums\AuditType;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => AuditType::Purchase,
            'description' => 'Purchased 1 x Widget',
            'properties' => [],
            'created_at' => now(),
        ];
    }
}
