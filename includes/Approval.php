<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Approval
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'handleActions']);
    }

    public function menu(): void
    {
        add_submenu_page(
            'users.php',
            'Validation FDS',
            'Validation FDS',
            'manage_options',
            'fds-approval',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $users = get_users([
            'meta_key' => '_fds_status',
            'meta_value' => 'pending',
        ]);

        echo '<div class="wrap"><h1>Validation FDS</h1>';

        if (empty($users)) {
            echo '<p>Aucune demande en attente.</p></div>';
            return;
        }

        echo '<table class="widefat striped"><thead><tr><th>Email</th><th>Action</th></tr></thead><tbody>';

        foreach ($users as $user) {
            $approveUrl = wp_nonce_url(
                add_query_arg(['page' => 'fds-approval', 'approve' => $user->ID], admin_url('users.php')),
                'fds_approve_' . $user->ID
            );

            $rejectUrl = wp_nonce_url(
                add_query_arg(['page' => 'fds-approval', 'reject' => $user->ID], admin_url('users.php')),
                'fds_reject_' . $user->ID
            );

            echo '<tr>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>
                <a class="button button-primary" href="' . esc_url($approveUrl) . '">Valider</a>
                <a class="button" href="' . esc_url($rejectUrl) . '" onclick="return confirm(\'Refuser cette demande ?\')">Refuser</a>
            </td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    public function handleActions(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['approve'])) {
            $userId = (int) $_GET['approve'];

            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'fds_approve_' . $userId)) {
                return;
            }

            $user = get_user_by('id', $userId);

            if ($user) {
                $user->set_role(FEVDI_FDS_ROLE);
                update_user_meta($userId, '_fds_status', 'approved');

                wp_mail(
                    $user->user_email,
                    'Votre accès FDS a été validé',
                    "Bonjour,\n\nVotre accès aux fiches de sécurité a été validé.\nVous pouvez désormais vous connecter à l’espace FDS.\n"
                );
            }

            wp_redirect(admin_url('users.php?page=fds-approval'));
            exit;
        }

        if (isset($_GET['reject'])) {
            $userId = (int) $_GET['reject'];

            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'fds_reject_' . $userId)) {
                return;
            }

            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user($userId);

            wp_redirect(admin_url('users.php?page=fds-approval'));
            exit;
        }
    }
}