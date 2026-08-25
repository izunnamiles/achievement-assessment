<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAchievementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'unlocked_at' => $this->unlocked_at,
            'achievement' => new AchievementResource($this->whenLoaded('achievement')),
        ];
    }
}
