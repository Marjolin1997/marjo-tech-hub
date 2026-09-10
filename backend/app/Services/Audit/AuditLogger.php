<?php

namespace App\Services\Audit;

use App\Models\AuditEvent;
use App\Models\User;

class AuditLogger
{
    private const SAFE_METADATA = [
        'added_roles', 'removed_roles', 'category_id', 'type', 'language', 'is_sensitive',
        'filename', 'mime_type', 'size', 'status', 'changed_fields',
    ];

    public function record(
        ?User $actor,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?string $resourceLabel = null,
        array $metadata = [],
        ?User $targetUser = null,
    ): AuditEvent {
        $safe = array_intersect_key($metadata, array_flip(self::SAFE_METADATA));

        return AuditEvent::query()->create([
            'actor_id' => $actor?->id,
            'target_user_id' => $targetUser?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'resource_label' => $this->safeLabel($resourceLabel),
            'metadata' => $safe === [] ? null : $safe,
        ]);
    }

    private function safeLabel(?string $label): ?string
    {
        if ($label === null) return null;
        return mb_substr(trim(preg_replace('/\s+/', ' ', $label)), 0, 160);
    }
}
