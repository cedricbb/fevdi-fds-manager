<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Plugin
{
    public function init(): void
    {
        $this->maybeInstall();

        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        (new Shortcodes())->register();
        (new Upload())->register();
        (new Download())->register();
        (new Product())->register();
        (new Login())->register();
        (new Register())->register();
        (new Approval())->register();
        (new Account())->register();
        (new Admin())->register();
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'fevdi-fds-manager',
            FEVDI_FDS_MANAGER_URL . 'assets/css/fds-manager.css',
            [],
            FEVDI_FDS_MANAGER_VERSION
        );

        wp_enqueue_script(
            'fevdi-fds-manager',
            FEVDI_FDS_MANAGER_URL . 'assets/js/fds-manager.js',
            [],
            FEVDI_FDS_MANAGER_VERSION,
            true
        );
    }

    public function maybeInstall(): void
    {
        if (get_option('fevdi_fds_manager_version') === FEVDI_FDS_MANAGER_VERSION) {
            return;
        }

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

        update_option('fevdi_fds_manager_version', FEVDI_FDS_MANAGER_VERSION);
    }
}
