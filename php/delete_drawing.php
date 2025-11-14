<?php
// delete_drawing.php
require_once 'config.php';
header('Content-Type: application/json');

// Check if request method is POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data from request body
$input = json_decode(file_get_contents('php://input'), true);
$drawingId = $input['drawing_id'] ?? null;

if (!$drawingId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Drawing ID is missing']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You need to log in to delete a drawing']);
    exit;
}

try {
    $db = getDbConnection();

    // Get the user's role and check if they are an admin
    $currentUserId = $_SESSION['user_id'];
    $currentUserRole = $_SESSION['role'] ?? 'user';

    if ($currentUserRole !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have the necessary permissions to delete this drawing']);
        exit;
    }

    // Check if the drawing exists and get its user ID
    $stmt = $db->prepare("SELECT user_id FROM drawings WHERE id = ?");
    $stmt->execute([$drawingId]);
    $drawing = $stmt->fetch();

    if (!$drawing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Drawing not found']);
        exit;
    }

    // Check if the drawing is owned by the current user or an admin
    if ($drawing['user_id'] != $currentUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have the necessary permissions to delete this drawing']);
        exit;
    }

    // Delete the drawing from the database
    $deleteStmt = $db->prepare("DELETE FROM drawings WHERE id = ?");
    $success = $deleteStmt->execute([$drawingId]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Drawing deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error deleting drawing from database']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>