<?php
// admin/add_social_platform.php - DÜZELTİLDİ
require_once '../config.php';
require_once '../functions.php';
session_start();
header('Content-Type: application/json');

$userRole = $_SESSION['user_role'] ?? 'user';
if ($userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only administrators can add social media platforms.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$emoji = trim($_POST['emoji'] ?? '');
$regex = trim($_POST['regex'] ?? '');

if (empty($name) || empty($emoji)) {
    echo json_encode(['success' => false, 'message' => 'Platform name and emoji are required.']);
    exit;
}

try {
    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO social_media_platforms (name, emoji, url_regex, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$name, $emoji, $regex]);

    echo json_encode(['success' => true, 'message' => 'Social media platform added successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add social media platform: ' . $e->getMessage()]);
}
?>
