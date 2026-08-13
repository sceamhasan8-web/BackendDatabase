<?php
require_once '../db.php';

header("Content-Type: application/json; charset=UTF-8");

$rawInput = file_get_contents("php://input");
$payload = json_decode($rawInput, true);

if (!$payload) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON payload"]);
    exit();
}

$collection = $payload['collection'] ?? $payload['collection_name'] ?? null;
$docId = $payload['docId'] ?? $payload['doc_id'] ?? null;
$schoolId = $payload['schoolId'] ?? $payload['school_id'] ?? 'PROGGA_DEFAULT';
$data = $payload['data'] ?? null;

if (!$collection || !$docId || $data === null) {
    echo json_encode(["status" => "error", "message" => "Collection, docId, and data are required"]);
    exit();
}

try {
    // Fetch existing document to merge top-level JSON properties if present
    $existingStmt = $conn->prepare("SELECT `data` FROM `app_documents` WHERE `collection_name` = ? AND `doc_id` = ? AND `school_id` = ? LIMIT 1");
    $existingStmt->execute([$collection, $docId, $schoolId]);
    $existingRow = $existingStmt->fetch();

    if ($existingRow && !empty($existingRow['data'])) {
        $existingData = json_decode($existingRow['data'], true);
        if (is_array($existingData) && is_array($data)) {
            $data = array_merge($existingData, $data);
        }
    }

    $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Upsert query into app_documents
    $stmt = $conn->prepare("
        INSERT INTO `app_documents` (`collection_name`, `doc_id`, `school_id`, `data`)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `updated_at` = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$collection, $docId, $schoolId, $jsonData]);

    // If saving user account, also update users table for fast user queries
    if ($collection === 'users') {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $displayName = $data['name'] ?? $data['displayName'] ?? null;
        $role = $data['role'] ?? 'user';
        $userSid = $data['schoolId'] ?? $schoolId;

        $userStmt = $conn->prepare("
            INSERT INTO `users` (`user_id`, `email`, `phone`, `display_name`, `role`, `school_id`, `data`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                `email` = VALUES(`email`),
                `phone` = VALUES(`phone`),
                `display_name` = VALUES(`display_name`),
                `role` = VALUES(`role`),
                `school_id` = VALUES(`school_id`),
                `data` = VALUES(`data`),
                `updated_at` = CURRENT_TIMESTAMP
        ");
        $userStmt->execute([$docId, $email, $phone, $displayName, $role, $userSid, $jsonData]);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Document saved successfully",
        "docId" => $docId,
        "collection" => $collection
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
