<?php
require_once '../db.php';

header("Content-Type: application/json; charset=UTF-8");

// Endpoint to query users table by user_id, phone, email, or eiin
$userId = $_GET['userId'] ?? $_GET['user_id'] ?? $_GET['id'] ?? null;
$phone = $_GET['phone'] ?? null;
$email = $_GET['email'] ?? null;

try {
    if ($userId) {
        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `user_id` = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(["status" => "success", "exists" => true, "data" => json_decode($user['data'], true)]);
        } else {
            // Also check app_documents collection 'users'
            $docStmt = $conn->prepare("SELECT `data` FROM `app_documents` WHERE `collection_name` = 'users' AND `doc_id` = ? LIMIT 1");
            $docStmt->execute([$userId]);
            $doc = $docStmt->fetch();
            if ($doc) {
                echo json_encode(["status" => "success", "exists" => true, "data" => json_decode($doc['data'], true)]);
            } else {
                echo json_encode(["status" => "success", "exists" => false, "data" => null]);
            }
        }
    } else if ($phone || $email) {
        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `phone` = ? OR `email` = ? LIMIT 1");
        $stmt->execute([$phone, $email]);
        $user = $stmt->fetch();
        if ($user) {
            echo json_encode(["status" => "success", "exists" => true, "data" => json_decode($user['data'], true)]);
        } else {
            echo json_encode(["status" => "success", "exists" => false, "data" => null]);
        }
    } else {
        $stmt = $conn->query("SELECT * FROM `users` ORDER BY `updated_at` DESC");
        $users = $stmt->fetchAll();
        $list = [];
        foreach ($users as $u) {
            $list[] = json_decode($u['data'], true);
        }
        echo json_encode(["status" => "success", "count" => count($list), "data" => $list]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
