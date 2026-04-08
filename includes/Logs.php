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
     * @return array<int, array<string, mixed>>
     */
    public static function getLogs(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'fevdi_fds_logs';

        $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY downloaded_at DESC LIMIT 500", ARRAY_A);

        return is_array($results) ? $results : [];
    }

    private static function getIpAddress(): string
    {
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field((string) $_SERVER['REMOTE_ADDR']) : '';
    }

    public static function getUserLogs(int $userId): array
{
    global $wpdb;
    $table = $wpdb->prefix . 'fevdi_fds_logs';

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY downloaded_at DESC LIMIT 50",
            $userId
        ),
        ARRAY_A
    );

    return is_array($results) ? $results : [];
}
}