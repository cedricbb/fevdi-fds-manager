<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Login
{
    public function register(): void
    {
        add_shortcode('fds_login_form', [$this, 'render']);
    }

    public function render(): string
    {
        if (is_user_logged_in()) {
            return '<p>Vous êtes déjà connecté.</p>';
        }

        return (string) wp_login_form([
            'echo' => false,
            'remember' => true,
            'redirect' => home_url('/espace-fds/'),
            'label_username' => 'Email ou identifiant',
            'label_password' => 'Mot de passe',
            'label_remember' => 'Se souvenir de moi',
            'label_log_in' => 'Connexion',
        ]);
    }
}