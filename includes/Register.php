<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Register
{
    public function register(): void
    {
        add_shortcode('fds_register_form', [$this, 'render']);
        add_action('init', [$this, 'handle']);
    }

    public function render(): string
    {
        if (is_user_logged_in()) {
            return '<p>Vous êtes déjà connecté.</p>';
        }

        $message = '';
        $error = '';

        if (isset($_GET['fds_register'])) {
            $status = sanitize_text_field((string) wp_unslash($_GET['fds_register']));

            if ($status === 'success') {
                $message = 'Demande envoyée. En attente de validation.';
            }

            if ($status === 'exists') {
                $error = 'Un compte existe déjà avec cet email.';
            }

            if ($status === 'invalid') {
                $error = 'Email invalide.';
            }

            if ($status === 'error') {
                $error = 'Impossible de créer le compte.';
            }
        }

        ob_start();
        ?>
        <?php if ($message !== '') : ?><div class="fds-notice"><?php echo esc_html($message); ?></div><?php endif; ?>
        <?php if ($error !== '') : ?><div class="fds-error"><?php echo esc_html($error); ?></div><?php endif; ?>

        <form method="post" class="fds-register-form">
            <?php wp_nonce_field('fds_register_action', 'fds_register_nonce'); ?>
            <input type="email" name="email" placeholder="Votre email" required>
            <button type="submit" name="fds_register_submit" class="fds-btn">Demander accès</button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    public function handle(): void
    {
        if (empty($_POST['fds_register_submit'])) {
            return;
        }

        if (!isset($_POST['fds_register_nonce']) || !wp_verify_nonce((string) $_POST['fds_register_nonce'], 'fds_register_action')) {
            return;
        }

        $email = sanitize_email((string) wp_unslash($_POST['email']));
        $redirect = $this->getCurrentPageUrl();

        if (!is_email($email)) {
            wp_redirect(add_query_arg(['fds_register' => 'invalid'], $redirect));
            exit;
        }

        if (email_exists($email)) {
            wp_redirect(add_query_arg(['fds_register' => 'exists'], $redirect));
            exit;
        }

        $password = wp_generate_password(20, true, true);
        $userId = wp_create_user($email, $password, $email);

        if (is_wp_error($userId)) {
            wp_redirect(add_query_arg(['fds_register' => 'error'], $redirect));
            exit;
        }

        update_user_meta($userId, '_fds_status', 'pending');

        wp_redirect(add_query_arg(['fds_register' => 'success'], $redirect));
        exit;
    }

    private function getCurrentPageUrl(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        return home_url((string) $requestUri);
    }
}