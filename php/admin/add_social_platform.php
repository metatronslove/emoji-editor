<?php
require_once '../config.php';
header('Content-Type: application/json');
session_start(); // Start session to access $_SESSION variables

// Validate user role
$userRole = sanitizeInput($_SESSION['user_role'] ?? '');
if ($userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only administrators can add social media platforms.']);
    exit;
}

// Sanitize inputs for XSS protection
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate and prepare user inputs for SQL Injection Protection
$name = sanitizeInput($_POST['name'] ?? '');
$emoji = sanitizeInput($_POST['emoji'] ?? '');
$regex = sanitizeInput($_POST['regex'] ?? '');

if (empty($name) || empty($emoji)) {
    echo json_encode(['success' => false, 'message' => 'Platform name and emoji are required.']);
    exit;
}

try {
    // Session hijacking protection by using session id in prepared statement
    $stmt = getDbConnection()->prepare("SELECT session_id FROM sessions WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->rowCount() === 0) {
        throw new Exception('Your session does not exist. Please log in again.');
    }

    // SQL Injection Protection using prepared statements and parameter binding
    $stmt = getDbConnection()->prepare("INSERT INTO social_media_platforms (name, emoji, url_regex, is_active) VALUES (:name, :emoji, :regex, 1)");
    $stmt->execute([
        ':name' => $name,
        ':emoji' => $emoji,
        ':regex' => $regex,
    ]);

    echo json_encode(['success' => true, 'message' => 'Social media platform added successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add social media platform: ' . $e->getMessage()]);
}
?>