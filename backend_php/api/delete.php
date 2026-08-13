<?php
require_once '../db.php';

header("Content-Type: application/json; charset=UTF-8");

$rawInput = file_get_contents("php://input");
$payload = json_decode($rawInput, true);

$collection = $_GET['collection'] ?? $payload['collection'] ?? $payload['collection_name'] ?? null;
$docId = $_GET['docId'] ?? $payload['docId'] ?? $payload['doc_id'] ?? null;
$schoolId = $_GET['schoolId'] ?? $payload['schoolId'] ?? $payload['school_id'] ?? null;

if (!$collection || !$docId) {
    echo json_encode(["status" => "error", "message" => "Collection and docId are required for deletion"]);
    exit();
}

try {
    if ($schoolId) {
        $stmt = $conn->prepare("DELETE FROM `app_documents` WHERE `collection_name` = ? AND `doc_id` = ? AND `school_id` = ?");
        $stmt->execute([$collection, $docId, $schoolId]);
    } else {
        $stmt = $conn->prepare("DELETE FROM `app_documents` WHERE `collection_name` = ? AND `doc_id` = ?");
        $stmt->execute([$collection, $docId]);
    }

    if ($collection === 'users') {
        $userStmt = $conn->prepare("DELETE FROM `users` WHERE `user_id` = ?");
        $userStmt->execute([$docId]);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Document deleted successfully",
        "docId" => $docId,
        "collection" => $collection
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
