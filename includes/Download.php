<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Download
{
    public function register(): void
    {
        add_action('init', [$this, 'handleDownload']);
    }

    public function handleDownload(): void
    {
        if (!isset($_GET[FEVDI_FDS_URL_PARAM])) {
            return;
        }

        $fileRaw = (string) wp_unslash($_GET[FEVDI_FDS_URL_PARAM]);
        $file = basename(urldecode($fileRaw));
        $dirKey = (isset($_GET['dir']) && $_GET['dir'] === 'multi') ? 'multi' : 'fr';
        $dir = $dirKey === 'multi' ? FEVDI_FDS_PATH_MULTI : FEVDI_FDS_PATH_FR;

        if ($file === '') {
            wp_die('Fichier invalide.');
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], 'fds_download_' . $file)) {
            wp_die('Lien de téléchargement invalide.');
        }

        if (!is_user_logged_in()) {
            $currentUrl = home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? '/'));
            wp_redirect(wp_login_url($currentUrl));
            exit;
        }

        $path = trailingslashit($dir) . $file;
        $realDir = realpath($dir);
        $realFile = realpath($path);

        if ($realDir === false || $realFile === false || !str_starts_with($realFile, $realDir) || !file_exists($realFile) || !is_file($realFile)) {
            wp_die('Fichier introuvable.');
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $mime = wp_check_filetype($realFile)['type'] ?: 'application/octet-stream';

        nocache_headers();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($realFile) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . (string) filesize($realFile));
        header('X-Robots-Tag: noindex, nofollow', true);

        readfile($realFile);
        exit;
    }
}