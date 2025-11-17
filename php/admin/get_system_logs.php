<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!Auth::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

try {
    $db = getDbConnection();

    $limit = 100;
    $page = $_GET['page'] ?? 1;
    $offset = ($page - 1) * $limit;

    $stmt = $db->prepare("
        SELECT l.*, u.username as admin_name
        FROM admin_logs l
        LEFT JOIN users u ON l.admin_id = u.id
        ORDER BY l.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Toplam log sayısı
    $count_stmt = $db->query("SELECT COUNT(*) FROM admin_logs");
    $total_logs = $count_stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total' => $total_logs,
        'page' => $page,
        'totalPages' => ceil($total_logs / $limit)
    ]);

} catch (Exception $e) {
    error_log("Get system logs error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Loglar yüklenirken hata oluştu'
    ]);
}
