<?php
require_once '../config.php';
require_once '../functions.php';
$userRole = $_SESSION['user_role'] ?? 'user';
if ($userRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden. Only admins can add platforms.']);
    exit;
}
$input = filter_input_array(INPUT_POST, [
    'name' => FILTER_SANITIZE_STRING,
    'emoji' => FILTER_SANITIZE_STRING,
    'regex' => FILTER_VALIDATE_REGEXP,
]);
if (empty($input['name']) || empty($input['emoji'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Platform name and emoji are required.']);
    exit;
}
$db = getDbConnection();
try {
    $stmt = $db->prepare("INSERT INTO social_media_platforms (name, emoji, url_regex, is_active) VALUES (?, ?, ?, 1)");
    $stmt->bindValue(1, htmlspecialchars($input['name'])); // XSS Protection
    $stmt->bindValue(2, htmlspecialchars($input['emoji'])); // XSS Protection
    $stmt->bindValue(3, $input['regex']);
    $result = $stmt->execute();
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Social media platform added successfully.']);
    } else {
        throw new Exception('Platform could not be added.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Platform addition failed: ' . $e->getMessage()]);
}
?>
