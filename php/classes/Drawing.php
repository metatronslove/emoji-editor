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

    public function addVote($voterId, $targetType, $targetId, $voteType) {
        try {
            $db = getDbConnection();

            // Mükerrer oy kontrolü
            $checkStmt = $db->prepare("
            SELECT id FROM votes
            WHERE voter_id = ? AND target_type = ? AND target_id = ?
            ");
            $checkStmt->execute([$voterId, $targetType, $targetId]);

            if ($checkStmt->fetch()) {
                // Oy zaten var, güncelle
                $updateStmt = $db->prepare("
                UPDATE votes SET vote_type = ?
                WHERE voter_id = ? AND target_type = ? AND target_id = ?
                ");
                return $updateStmt->execute([$voteType, $voterId, $targetType, $targetId]);
            } else {
                // Yeni oy ekle
                $insertStmt = $db->prepare("
                INSERT INTO votes (voter_id, target_type, target_id, vote_type)
                VALUES (?, ?, ?, ?)
                ");
                return $insertStmt->execute([$voterId, $targetType, $targetId, $voteType]);
            }

        } catch (PDOException $e) {
            error_log("Oy ekleme hatası: " . $e->getMessage());
            return false;
        }
    }

    public function getVoteCount($targetType, $targetId) {
        try {
            $db = getDbConnection();

            $stmt = $db->prepare("
            SELECT
            SUM(CASE WHEN vote_type = 'up' THEN 1 ELSE 0 END) as upvotes,
                                 SUM(CASE WHEN vote_type = 'down' THEN 1 ELSE 0 END) as downvotes
                                 FROM votes
                                 WHERE target_type = ? AND target_id = ?
                                 ");
            $stmt->execute([$targetType, $targetId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Oy sayısı getirme hatası: " . $e->getMessage());
            return ['upvotes' => 0, 'downvotes' => 0];
        }
    }
}
