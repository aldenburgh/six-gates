<?php

namespace SixGates\Repositories;

use \PDO;

class AuditLogRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function log(string $action, string $entityType, ?string $entityId, string $source, bool $success, ?string $errorMessage = null, array $requestData = [], array $responseData = []): void
    {
        $id = \Ramsey\Uuid\Uuid::uuid4()->toString();

        $sql = "INSERT INTO audit_log (
            id, timestamp, action, entity_type, entity_id, source, success, error_message, request_data, response_data, user_agent, ip_address
        ) VALUES (
            :id, NOW(), :action, :entity_type, :entity_id, :source, :success, :error_message, :req_data, :res_data, 'system', '127.0.0.1'
        )";

        // Ensure data is json
        $reqJson = empty($requestData) ? null : json_encode($requestData);
        $resJson = empty($responseData) ? null : json_encode($responseData);

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'source' => $source,
            'success' => $success ? 1 : 0,
            'error_message' => $errorMessage,
            'req_data' => $reqJson,
            'res_data' => $resJson
        ]);
    }
}
