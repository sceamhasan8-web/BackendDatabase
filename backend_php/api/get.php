<?php
require_once '../db.php';

header("Content-Type: application/json; charset=UTF-8");

$collection = $_GET['collection'] ?? $_GET['collection_name'] ?? null;
$docId = $_GET['docId'] ?? $_GET['doc_id'] ?? null;
$schoolId = $_GET['schoolId'] ?? $_GET['school_id'] ?? null;

if (!$collection) {
    echo json_encode(["status" => "error", "message" => "Collection name is required"]);
    exit();
}

try {
    if ($docId) {
        // Query specific document
        if ($schoolId) {
            $stmt = $conn->prepare("SELECT `doc_id`, `school_id`, `data`, `updated_at` FROM `app_documents` WHERE `collection_name` = ? AND `doc_id` = ? AND `school_id` = ? LIMIT 1");
            $stmt->execute([$collection, $docId, $schoolId]);
        } else {
            $stmt = $conn->prepare("SELECT `doc_id`, `school_id`, `data`, `updated_at` FROM `app_documents` WHERE `collection_name` = ? AND `doc_id` = ? LIMIT 1");
            $stmt->execute([$collection, $docId]);
        }
        $row = $stmt->fetch();

        if (!$row && $schoolId) {
            // Fallback without school_id constraint
            $stmtFallback = $conn->prepare("SELECT `doc_id`, `school_id`, `data`, `updated_at` FROM `app_documents` WHERE `collection_name` = ? AND `doc_id` = ? LIMIT 1");
            $stmtFallback->execute([$collection, $docId]);
            $row = $stmtFallback->fetch();
        }

        if ($row) {
            $parsedData = json_decode($row['data'], true);
            echo json_encode([
                "status" => "success",
                "exists" => true,
                "docId" => $row['doc_id'],
                "schoolId" => $row['school_id'],
                "updatedAt" => $row['updated_at'],
                "data" => $parsedData
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "exists" => false,
                "data" => null
            ]);
        }
    } else {
        // Fetch all documents in collection
        if ($schoolId) {
            $stmt = $conn->prepare("SELECT `doc_id`, `school_id`, `data`, `updated_at` FROM `app_documents` WHERE `collection_name` = ? AND (`school_id` = ? OR `school_id` = 'PROGGA_DEFAULT')");
            $stmt->execute([$collection, $schoolId]);
        } else {
            $stmt = $conn->prepare("SELECT `doc_id`, `school_id`, `data`, `updated_at` FROM `app_documents` WHERE `collection_name` = ?");
            $stmt->execute([$collection]);
        }
        $rows = $stmt->fetchAll();

        $items = [];
        foreach ($rows as $r) {
            $parsed = json_decode($r['data'], true);
            if (is_array($parsed)) {
                $parsed['id'] = $r['doc_id'];
                $parsed['_docId'] = $r['doc_id'];
            }
            $items[] = [
                "docId" => $r['doc_id'],
                "schoolId" => $r['school_id'],
                "updatedAt" => $r['updated_at'],
                "data" => $parsed
            ];
        }

        echo json_encode([
            "status" => "success",
            "count" => count($items),
            "data" => $items
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
