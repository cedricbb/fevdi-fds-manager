<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Upload
{
    /** @var array<int, string> */
    private array $allowedExtensions = ['pdf'];

    public function register(): void
    {
        add_shortcode('fds_upload', [$this, 'renderUpload']);
    }

    public function renderUpload(): string
    {
        if (!current_user_can('manage_options')) {
            return '';
        }

        $message = '';
        $error = '';

        if (isset($_GET['fds_upload_status'])) {
            $status = sanitize_text_field((string) wp_unslash($_GET['fds_upload_status']));

            if ($status === 'success') {
                $message = 'Upload terminé.';
            }

            if ($status === 'error' && isset($_GET['fds_upload_msg'])) {
                $error = sanitize_text_field((string) wp_unslash($_GET['fds_upload_msg']));
            }
        }

        if (isset($_POST['fds_upload_submit'])) {
            $this->handleUpload();
        }

        ob_start();
        ?>
        <?php if ($message !== '') : ?>
        <div class="fds-notice"><?php echo esc_html($message); ?></div>
    <?php endif; ?>

        <?php if ($error !== '') : ?>
        <div class="fds-error"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="fds-upload-form">
            <?php wp_nonce_field('fds_upload_action', 'fds_upload_nonce'); ?>

            <select name="fds_dir">
                <option value="fr">Français</option>
                <option value="multi">Multilingue</option>
            </select>

            <input type="file" name="fds_files[]" multiple accept="application/pdf">

            <button type="submit" name="fds_upload_submit" class="fds-btn">Upload</button>
        </form>
        <?php

        return (string) ob_get_clean();
    }

    private function handleUpload(): void
    {
        if (!isset($_POST['fds_upload_nonce']) || !wp_verify_nonce((string) $_POST['fds_upload_nonce'], 'fds_upload_action')) {
            $this->redirectWithError('Nonce invalide.');
        }

        $dir = (isset($_POST['fds_dir']) && $_POST['fds_dir'] === 'multi')
                ? FEVDI_FDS_PATH_MULTI
                : FEVDI_FDS_PATH_FR;

        if (!file_exists($dir) && !wp_mkdir_p($dir)) {
            $this->redirectWithError('Impossible de créer le dossier cible.');
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            $this->redirectWithError('Le dossier cible n’est pas accessible en écriture.');
        }

        if (empty($_FILES['fds_files']['name']) || !is_array($_FILES['fds_files']['name'])) {
            $this->redirectWithError('Aucun fichier reçu.');
        }

        $uploaded = 0;

        foreach ($_FILES['fds_files']['name'] as $key => $originalName) {
            $tmp = $_FILES['fds_files']['tmp_name'][$key] ?? '';
            $errorCode = (int) ($_FILES['fds_files']['error'][$key] ?? UPLOAD_ERR_NO_FILE);

            if ($errorCode !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($tmp === '' || !is_uploaded_file($tmp)) {
                continue;
            }

            $name = sanitize_file_name((string) wp_unslash($originalName));
            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($ext, $this->allowedExtensions, true)) {
                continue;
            }

            $destination = trailingslashit($dir) . $name;

            if (move_uploaded_file($tmp, $destination)) {
                $uploaded++;
            }
        }

        if ($uploaded < 1) {
            $this->redirectWithError('Aucun fichier PDF n’a pu être uploadé.');
        }

        wp_redirect(
                add_query_arg(
                        ['fds_upload_status' => 'success'],
                        $this->getCurrentPageUrl()
                )
        );
        exit;
    }

    private function redirectWithError(string $message): void
    {
        wp_redirect(
                add_query_arg(
                        [
                                'fds_upload_status' => 'error',
                                'fds_upload_msg' => rawurlencode($message),
                        ],
                        $this->getCurrentPageUrl()
                )
        );
        exit;
    }

    private function getCurrentPageUrl(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        return home_url((string) $requestUri);
    }
}