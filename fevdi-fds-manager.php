<?php

/**
 * Plugin Name: FEVdi FDS Manager
 * Description: Gestion privée des FDS (scan dossiers + upload + téléchargement sécurisé)
 * Version: 2.2.0
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const FEVDI_FDS_MANAGER_FILE = __FILE__;
const FEVDI_FDS_MANAGER_VERSION = '2.2.0';
const FEVDI_FDS_ROLE = 'fevdi_fds_customer';
define('FEVDI_FDS_MANAGER_PATH', plugin_dir_path(__FILE__));
define('FEVDI_FDS_MANAGER_URL', plugin_dir_url(__FILE__));
const FEVDI_FDS_PATH_FR = WP_CONTENT_DIR . '/uploads/FDS/Francais';
const FEVDI_FDS_PATH_MULTI = WP_CONTENT_DIR . '/uploads/FDS/Multilingue';
const FEVDI_FDS_URL_PARAM = 'fds_download';

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'Fevdi\\FdsManager\\')) {
        return;
    }

    $relative = str_replace('Fevdi\\FdsManager\\', '', $class);
    $relative = str_replace('\\', '/', $relative);
    $file = FEVDI_FDS_MANAGER_PATH . 'includes/' . $relative . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, static function (): void {
    add_role(FEVDI_FDS_ROLE, 'Client FDS', ['read' => true]);

    global $wpdb;
    $table = $wpdb->prefix . 'fevdi_fds_logs';
    $charsetCollate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta(
        "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            file_name varchar(255) NOT NULL DEFAULT '',
            dir_key varchar(20) NOT NULL DEFAULT '',
            downloaded_at datetime NOT NULL,
            ip_address varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY downloaded_at (downloaded_at),
            KEY dir_key (dir_key)
        ) {$charsetCollate};"
    );
});

add_action('plugins_loaded', static function (): void {
    (new Fevdi\FdsManager\Plugin())->init();
});
