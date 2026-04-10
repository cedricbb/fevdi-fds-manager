<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Logs
{
    public static function addLog(int $userId, string $fileName, string $dirKey): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fevdi_fds_logs';

        $wpdb->insert(
            $table,
            [
                'user_id' => $userId,
                'file_name' => $fileName,
                'dir_key' => $dirKey,
                'downloaded_at' => current_time('mysql'),
                'ip_address' => self::getIpAddress(),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * @param array{search?:string,dir?:string,user_id?:int,date_from?:string,date_to?:string,limit?:int,offset?:int} $filters
     * @return array<int, array<string, mixed>>
     */
    public static function getLogs(array $filters = []): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fevdi_fds_logs';

        $limit = min(5000, max(1, (int) ($filters['limit'] ?? 500)));
        $offset = max(0, (int) ($filters['offset'] ?? 0));
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = 'file_name LIKE %s';
            $params[] = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
        }

        if (!empty($filters['dir']) && in_array($filters['dir'], ['fr', 'multi'], true)) {
            $where[] = 'dir_key = %s';
            $params[] = (string) $filters['dir'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'downloaded_at >= %s';
            $params[] = (string) $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'downloaded_at <= %s';
            $params[] = (string) $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY downloaded_at DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * @param array{search?:string,dir?:string,user_id?:int,date_from?:string,date_to?:string} $filters
     */
    public static function countLogs(array $filters = []): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fevdi_fds_logs';
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = 'file_name LIKE %s';
            $params[] = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
        }

        if (!empty($filters['dir']) && in_array($filters['dir'], ['fr', 'multi'], true)) {
            $where[] = 'dir_key = %s';
            $params[] = (string) $filters['dir'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $params[] = (int) $filters['user_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'downloaded_at >= %s';
            $params[] = (string) $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'downloaded_at <= %s';
            $params[] = (string) $filters['date_to'] . ' 23:59:59';
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE " . implode(' AND ', $where);
        $prepared = empty($params) ? $sql : $wpdb->prepare($sql, $params);

        return (int) $wpdb->get_var($prepared);
    }

    private static function getIpAddress(): string
    {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getUserLogs(int $userId): array
    {
        return self::getLogs([
            'user_id' => $userId,
            'limit' => 50,
        ]);
    }
}
