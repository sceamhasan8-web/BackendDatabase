<?php
require_once '../db.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "POST request required"]);
    exit();
}

if (!isset($_FILES['file'])) {
    echo json_encode(["status" => "error", "message" => "No file uploaded"]);
    exit();
}

$file = $_FILES['file'];
$uploadDir = '../uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file['name']));
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $fileUrl = "$protocol://$host/uploads/$fileName";

    try {
        $stmt = $conn->prepare("INSERT INTO `uploads` (`file_name`, `file_path`, `mime_type`) VALUES (?, ?, ?)");
        $stmt->execute([$fileName, $fileUrl, $file['type'] ?? '']);
    } catch (Exception $e) {
        // Ignore DB log error if table missing
    }

    echo json_encode([
        "status" => "success",
        "url" => $fileUrl,
        "downloadURL" => $fileUrl,
        "fileName" => $fileName
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to save file on server"]);
}
?>
