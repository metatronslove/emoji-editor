<?php
// Drawing.php
// Çizim veritabanı işlemlerini (CRUD) yöneten Model sınıfı
require_once 'DB.php';

class Drawing {
    private $db;

    public function __construct() {
        $this->db = DB::getInstance()->getConnection();
    }

    /**
     * ID ile tek bir çizimin tüm detaylarını çeker.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT d.*, u.username FROM drawings d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
        $stmt->execute([$id]);
        $drawing = $stmt->fetch();
        return $drawing ?: null;
    }

    /**
     * Yeni bir çizim kaydı oluşturur.
     */
    public function create(int $userId, string $content, string $category = 'Genel'): bool {
        $stmt = $this->db->prepare("INSERT INTO drawings (user_id, content, category) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $content, $category]);
    }

    /**
     * Ana sayfa veya akış için en son halka açık çizimleri çeker.
     */
    public function getRecentPublicDrawings(int $limit = 20): array {
        $stmt = $this->db->prepare("
        SELECT d.id, d.content, d.updated_at, u.username
        FROM drawings d
        JOIN users u ON d.user_id = u.id
        WHERE d.is_visible = TRUE
        ORDER BY d.updated_at DESC
        LIMIT ?
        ");
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
