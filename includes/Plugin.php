<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Plugin
{
    public function init(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        (new Shortcodes())->register();
        (new Upload())->register();
        (new Download())->register();
        (new Product())->register();
        (new Login())->register();
        (new Register())->register();
        (new Approval())->register();
        (new Account())->register();
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style(
            'fevdi-fds-manager',
            FEVDI_FDS_MANAGER_URL . 'assets/css/fds-manager.css',
            [],
            '2.1.0'
        );

        wp_enqueue_script(
            'fevdi-fds-manager',
            FEVDI_FDS_MANAGER_URL . 'assets/js/fds-manager.js',
            [],
            '2.1.0',
            true
        );
    }
}