<?php

class SystemAdminMessageCenter
{
    public static function create(
        ?int $sender_user_id,
        string $sender_name,
        string $sender_email,
        string $feedback_type,
        string $subject,
        string $content,
        string $page_url
    ): int {
        $db = Tools::getDb();

        try {
            $stmt = $db->prepare(
                'INSERT INTO system_admin_messages
                (sender_user_id, sender_name, sender_email, feedback_type, subject, content, page_url)
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            if (!$stmt) {
                throw new Exception('Failed to prepare message insert.');
            }

            $stmt->bind_param(
                'issssss',
                $sender_user_id,
                $sender_name,
                $sender_email,
                $feedback_type,
                $subject,
                $content,
                $page_url
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to create system admin message.');
            }

            $message_id = (int)$db->insert_id;
            $stmt->close();
            $db->close();

            return $message_id;
        } catch (Exception $e) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            $db->close();
            throw $e;
        }
    }

    public static function getPage(int $page = 1, int $per_page = 20, string $filter = 'all'): array
    {
        $page = max(1, $page);
        $per_page = max(1, min(100, $per_page));
        $offset = ($page - 1) * $per_page;

        $filter = in_array($filter, ['all', 'unread', 'read', 'resolved'], true) ? $filter : 'all';

        $where = '1=1';
        if ($filter === 'unread') {
            $where = 'is_read = 0';
        } elseif ($filter === 'read') {
            $where = 'is_read = 1';
        } elseif ($filter === 'resolved') {
            $where = 'is_resolved = 1';
        }

        $db = Tools::getDb();

        try {
            $count_stmt = $db->prepare('SELECT COUNT(*) FROM system_admin_messages WHERE ' . $where);
            if (!$count_stmt) {
                throw new Exception('Failed to prepare count query.');
            }

            $count_stmt->execute();
            $count_stmt->bind_result($total);
            $count_stmt->fetch();
            $count_stmt->close();

            $stmt = $db->prepare(
                'SELECT id, sender_user_id, sender_name, sender_email, feedback_type, subject, content, page_url,
                        is_read, is_resolved, read_at, resolved_at, created_at
                 FROM system_admin_messages
                 WHERE ' . $where . '
                 ORDER BY created_at DESC, id DESC
                 LIMIT ? OFFSET ?'
            );
            if (!$stmt) {
                throw new Exception('Failed to prepare list query.');
            }

            $stmt->bind_param('ii', $per_page, $offset);
            $stmt->execute();
            $stmt->bind_result(
                $id,
                $sender_user_id,
                $sender_name,
                $sender_email,
                $feedback_type,
                $subject,
                $content,
                $page_url,
                $is_read,
                $is_resolved,
                $read_at,
                $resolved_at,
                $created_at
            );

            $messages = [];
            while ($stmt->fetch()) {
                $messages[] = [
                    'id' => (int)$id,
                    'sender_user_id' => $sender_user_id !== null ? (int)$sender_user_id : null,
                    'sender_name' => (string)$sender_name,
                    'sender_email' => (string)$sender_email,
                    'feedback_type' => (string)$feedback_type,
                    'subject' => (string)$subject,
                    'content' => (string)$content,
                    'page_url' => (string)$page_url,
                    'is_read' => (int)$is_read,
                    'is_resolved' => (int)$is_resolved,
                    'read_at' => $read_at,
                    'resolved_at' => $resolved_at,
                    'created_at' => (string)$created_at,
                ];
            }
            $stmt->close();

            $counts = self::getCounts($db);
            $db->close();

            return [
                'messages' => $messages,
                'total' => (int)$total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => max(1, (int)ceil(((int)$total) / $per_page)),
                'counts' => $counts,
                'filter' => $filter,
            ];
        } catch (Exception $e) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            if (isset($count_stmt) && $count_stmt instanceof mysqli_stmt) {
                $count_stmt->close();
            }
            $db->close();
            throw $e;
        }
    }

    public static function setReadState(int $id, bool $is_read): void
    {
        $db = Tools::getDb();

        try {
            if ($is_read) {
                $stmt = $db->prepare('UPDATE system_admin_messages SET is_read = 1, read_at = NOW() WHERE id = ?');
            } else {
                $stmt = $db->prepare('UPDATE system_admin_messages SET is_read = 0, read_at = NULL WHERE id = ?');
            }
            if (!$stmt) {
                throw new Exception('Failed to prepare read state update.');
            }

            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update read state.');
            }
            $stmt->close();
            $db->close();
        } catch (Exception $e) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            $db->close();
            throw $e;
        }
    }

    public static function setResolvedState(int $id, bool $is_resolved): void
    {
        $db = Tools::getDb();

        try {
            if ($is_resolved) {
                $stmt = $db->prepare(
                    'UPDATE system_admin_messages
                     SET is_resolved = 1, resolved_at = NOW(), is_read = 1, read_at = COALESCE(read_at, NOW())
                     WHERE id = ?'
                );
            } else {
                $stmt = $db->prepare('UPDATE system_admin_messages SET is_resolved = 0, resolved_at = NULL WHERE id = ?');
            }
            if (!$stmt) {
                throw new Exception('Failed to prepare resolved state update.');
            }

            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update resolved state.');
            }
            $stmt->close();
            $db->close();
        } catch (Exception $e) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            $db->close();
            throw $e;
        }
    }

    public static function nullSenderReferences(int $sender_user_id): void
    {
        $db = Tools::getDb();

        try {
            $stmt = $db->prepare('UPDATE system_admin_messages SET sender_user_id = NULL WHERE sender_user_id = ?');
            if (!$stmt) {
                throw new Exception('Failed to prepare sender nulling update.');
            }

            $stmt->bind_param('i', $sender_user_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to null sender references.');
            }

            $stmt->close();
            $db->close();
        } catch (Exception $e) {
            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }
            $db->close();
            throw $e;
        }
    }

    private static function getCounts(mysqli $db): array
    {
        $counts = [
            'all' => 0,
            'unread' => 0,
            'read' => 0,
            'resolved' => 0,
        ];

        $stmt = $db->prepare(
            'SELECT
                COUNT(*) AS all_count,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread_count,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) AS read_count,
                SUM(CASE WHEN is_resolved = 1 THEN 1 ELSE 0 END) AS resolved_count
             FROM system_admin_messages'
        );

        if (!$stmt) {
            return $counts;
        }

        $stmt->execute();
        $stmt->bind_result($all_count, $unread_count, $read_count, $resolved_count);
        if ($stmt->fetch()) {
            $counts = [
                'all' => (int)$all_count,
                'unread' => (int)$unread_count,
                'read' => (int)$read_count,
                'resolved' => (int)$resolved_count,
            ];
        }
        $stmt->close();

        return $counts;
    }
}
