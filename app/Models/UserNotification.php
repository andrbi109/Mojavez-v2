<?php
/**
 * مدل اعلان‌های کاربر (UserNotification)
 */
class UserNotification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, a.tracking_code
             FROM user_notifications n
             LEFT JOIN applicants a ON a.id = n.applicant_id
             WHERE n.user_account_id = :uid
             ORDER BY n.created_at DESC
             LIMIT " . (int)$limit
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function countUnread(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM user_notifications WHERE user_account_id = :uid AND is_read = 0"
        );
        $stmt->execute(['uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(int $notifId, int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_notifications SET is_read = 1 WHERE id = :id AND user_account_id = :uid"
        );
        $stmt->execute(['id' => $notifId, 'uid' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE user_notifications SET is_read = 1 WHERE user_account_id = :uid AND is_read = 0"
        );
        $stmt->execute(['uid' => $userId]);
    }

    public function create(int $userId, string $type, string $title, string $body = '', ?int $applicantId = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO user_notifications (user_account_id, applicant_id, type, title, body)
             VALUES (:uid, :aid, :type, :title, :body)"
        );
        $stmt->execute([
            'uid' => $userId,
            'aid' => $applicantId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
        ]);
    }
}
