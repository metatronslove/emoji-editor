<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Only admins can change rank settings.']);
    exit;
}

// Validate user input and sanitize it using PDO prepared statements
$input = filter_var_array($_POST, FILTER_VALIDATE_INT);
if (empty($input)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

try {
    // Establish a connection to the database using PDO
    $db = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);

    // Start a transaction for more efficient error handling
    $db->beginTransaction();

    foreach ($input as $key => $value) {
        // Prepare the statement with placeholders for each value and execute it using PDO's prepare() method
        $stmt = $db->prepare("INSERT INTO rank_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, htmlspecialchars($value), htmlspecialchars($value)]);
    }

    // Commit the transaction
    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Rank settings saved.']);
} catch (PDOException $e) {
    // In case of an error, rollback the transaction and return the error message
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error saving rank settings: ' . htmlspecialchars($e->getMessage())]);
} catch (Exception $e) {
    // Handle any other exceptions not related to PDO errors
    echo json_encode(['success' => false, 'message' => 'Error saving rank settings: ' . htmlspecialchars($e->getMessage())]);
}
?>