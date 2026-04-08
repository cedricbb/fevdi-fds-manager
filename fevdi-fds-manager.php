<?php

/**
 * Plugin Name: FEVdi FDS Manager
 * Description: Gestion privée des FDS (scan dossiers + upload + téléchargement sécurisé)
 * Version: 2.1.0
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const FEVDI_FDS_MANAGER_FILE = __FILE__;
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

add_action('plugins_loaded', static function (): void {
    (new Fevdi\FdsManager\Plugin())->init();
});