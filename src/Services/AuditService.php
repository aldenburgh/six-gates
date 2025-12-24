<?php

namespace SixGates\Services;

use SixGates\Repositories\AuditLogRepository;

class AuditService
{
    private AuditLogRepository $repo;

    public function __construct(AuditLogRepository $repo)
    {
        $this->repo = $repo;
    }

    public function logSuccess(string $action, string $entityType, ?string $entityId, array $data = []): void
    {
        $this->repo->log($action, $entityType, $entityId, 'system', true, null, $data);
    }

    public function logFailure(string $action, string $entityType, ?string $entityId, string $error, array $data = []): void
    {
        $this->repo->log($action, $entityType, $entityId, 'system', false, $error, $data);
    }
}
