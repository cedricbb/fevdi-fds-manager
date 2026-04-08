<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Account
{
    public function register(): void
    {
        add_shortcode('fds_account', [$this, 'render']);
    }

    public function render(): string
    {
        if (!is_user_logged_in()) {
            return '<p>Veuillez vous connecter pour accéder à votre espace FDS.</p>';
        }

        $userId = get_current_user_id();
        $status = (string) get_user_meta($userId, '_fds_status', true);

        if ($status !== 'approved' && !current_user_can('manage_options')) {
            return '<p>Votre compte est en attente de validation.</p>';
        }

        $logs = Logs::getUserLogs($userId);

        ob_start();
        ?>
        <div class="fds-account">
            <h2>Mon espace FDS</h2>
            <p><a class="fds-btn" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Se déconnecter</a></p>

            <h3>Mes téléchargements récents</h3>

            <?php if (empty($logs)) : ?>
                <p>Aucun téléchargement pour le moment.</p>
            <?php else : ?>
                <table class="fds-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Fichier</th>
                            <th>Répertoire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html((string) $log['downloaded_at']); ?></td>
                                <td><?php echo esc_html((string) $log['file_name']); ?></td>
                                <td><?php echo esc_html((string) $log['dir_key']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php

        return (string) ob_get_clean();
    }
}