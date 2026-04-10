<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Admin
{
    private const NONCE_ACTION = 'fevdi_fds_admin';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_ajax_fevdi_fds_admin_list', [$this, 'ajaxList']);
        add_action('wp_ajax_fevdi_fds_admin_upload', [$this, 'ajaxUpload']);
        add_action('wp_ajax_fevdi_fds_admin_delete', [$this, 'ajaxDelete']);
        add_action('admin_post_fevdi_fds_export_logs', [$this, 'exportLogs']);
    }

    public function menu(): void
    {
        add_menu_page(
            'FDS Manager',
            'FDS Manager',
            'manage_options',
            'fevdi-fds-manager',
            [$this, 'render'],
            'dashicons-media-document',
            56
        );
    }

    public function enqueue(string $hook): void
    {
        if ($hook !== 'toplevel_page_fevdi-fds-manager') {
            return;
        }

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

        wp_localize_script(
            'fevdi-fds-manager',
            'FevdiFdsManager',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'exportUrl' => admin_url('admin-post.php?action=fevdi_fds_export_logs'),
                'nonce' => wp_create_nonce(self::NONCE_ACTION),
            ]
        );
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $logs = Logs::getLogs(['limit' => 10]);
        ?>
        <div class="wrap fds-admin">
            <div class="fds-admin-hero">
                <div>
                    <p class="fds-kicker">Gestion documentaire</p>
                    <h1>FDS Manager</h1>
                    <p>Administrez les fiches, contrôlez les téléchargements et gardez les fichiers produits à jour.</p>
                </div>
                <a class="fds-admin-btn fds-admin-btn-secondary fds-export-logs" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=fevdi_fds_export_logs'), self::NONCE_ACTION)); ?>">Exporter les logs CSV</a>
            </div>

            <div class="fds-admin-grid">
                <section class="fds-admin-panel">
                    <div class="fds-panel-heading">
                        <div>
                            <h2>Bibliothèque FDS</h2>
                            <p>Trouvez rapidement les fiches à contrôler.</p>
                        </div>
                        <span class="fds-table-count" data-fds-count>Chargement...</span>
                    </div>

                    <form class="fds-admin-filters" data-fds-filters>
                        <input type="search" name="search" placeholder="Nom du fichier ou produit">
                        <select name="dir">
                            <option value="fr">Français</option>
                            <option value="multi">Multilingue</option>
                        </select>
                        <select name="ext">
                            <option value="">Tous les types</option>
                            <option value="pdf">PDF</option>
                        </select>
                        <input type="date" name="date_from" aria-label="Date min">
                        <input type="date" name="date_to" aria-label="Date max">
                        <select name="per_page">
                            <option value="10">10 / page</option>
                            <option value="25">25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                    </form>

                    <div class="fds-admin-table-shell">
                        <table class="fds-table fds-admin-table" data-fds-admin-table>
                            <thead>
                            <tr>
                                <th><button type="button" data-sort="ext">Type</button></th>
                                <th><button type="button" data-sort="name">Nom</button></th>
                                <th><button type="button" data-sort="size">Taille</button></th>
                                <th><button type="button" data-sort="date">Modification</button></th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody data-fds-rows>
                            <tr><td colspan="5">Chargement...</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="fds-admin-pagination" data-fds-admin-pagination></div>
                </section>

                <aside class="fds-admin-panel fds-upload-panel">
                    <h2>Ajouter des fichiers</h2>
                    <p>Déposez une ou plusieurs FDS PDF dans le répertoire cible.</p>
                    <form class="fds-admin-upload" data-fds-upload enctype="multipart/form-data">
                        <select name="fds_dir">
                            <option value="fr">Français</option>
                            <option value="multi">Multilingue</option>
                        </select>
                        <label class="fds-dropzone">
                            <span>Choisir des PDF</span>
                            <input type="file" name="fds_files[]" multiple accept="application/pdf">
                        </label>
                        <button type="submit" class="fds-admin-btn">Uploader</button>
                    </form>
                    <div class="fds-admin-message" data-fds-upload-message></div>
                </aside>
            </div>

            <section class="fds-admin-panel fds-logs-panel">
                <div class="fds-panel-heading">
                    <div>
                        <h2>Derniers téléchargements</h2>
                        <p>Historique récent des accès aux fiches.</p>
                    </div>
                </div>

                <div class="fds-admin-table-shell">
                    <table class="fds-table fds-admin-table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Fichier</th>
                            <th>Répertoire</th>
                            <th>IP</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($logs)) : ?>
                            <tr><td colspan="5">Aucun téléchargement enregistré.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($logs as $log) : ?>
                            <?php $user = get_user_by('id', (int) $log['user_id']); ?>
                            <tr>
                                <td><?php echo esc_html((string) $log['downloaded_at']); ?></td>
                                <td><?php echo esc_html($user ? $user->user_email : 'Utilisateur #' . (string) $log['user_id']); ?></td>
                                <td><?php echo esc_html((string) $log['file_name']); ?></td>
                                <td><?php echo esc_html((string) $log['dir_key']); ?></td>
                                <td><?php echo esc_html((string) $log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
        <?php
    }

    public function ajaxList(): void
    {
        $this->guardAjax();

        $filters = $this->getFileFilters($_POST);
        $files = Scanner::getFiles($filters['dir']);
        $files = $this->filterFiles($files, $filters);
        $total = count($files);

        $this->sortFiles($files, $filters['sort'], $filters['order']);

        $totalPages = max(1, (int) ceil($total / $filters['per_page']));
        $page = min($filters['page'], $totalPages);
        $files = array_slice($files, ($page - 1) * $filters['per_page'], $filters['per_page']);

        wp_send_json_success([
            'rows' => $this->renderRows($files, $filters['dir']),
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'summary' => sprintf('%d fichier%s', $total, $total > 1 ? 's' : ''),
        ]);
    }

    public function ajaxUpload(): void
    {
        $this->guardAjax();

        $dirKey = (isset($_POST['fds_dir']) && $_POST['fds_dir'] === 'multi') ? 'multi' : 'fr';
        $dir = $this->pathForDir($dirKey);

        if (!file_exists($dir) && !wp_mkdir_p($dir)) {
            wp_send_json_error(['message' => 'Impossible de créer le dossier cible.'], 500);
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            wp_send_json_error(['message' => 'Le dossier cible n’est pas accessible en écriture.'], 500);
        }

        if (empty($_FILES['fds_files']['name']) || !is_array($_FILES['fds_files']['name'])) {
            wp_send_json_error(['message' => 'Aucun fichier reçu.'], 400);
        }

        $uploaded = 0;
        $skipped = 0;

        foreach ($_FILES['fds_files']['name'] as $key => $originalName) {
            $tmp = $_FILES['fds_files']['tmp_name'][$key] ?? '';
            $errorCode = (int) ($_FILES['fds_files']['error'][$key] ?? UPLOAD_ERR_NO_FILE);

            if ($errorCode !== UPLOAD_ERR_OK || $tmp === '' || !is_uploaded_file($tmp)) {
                $skipped++;
                continue;
            }

            $name = sanitize_file_name((string) wp_unslash($originalName));
            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $skipped++;
                continue;
            }

            $destination = trailingslashit($dir) . $name;

            if (move_uploaded_file($tmp, $destination)) {
                $uploaded++;
            } else {
                $skipped++;
            }
        }

        if ($uploaded < 1) {
            wp_send_json_error(['message' => 'Aucun fichier PDF n’a pu être uploadé.'], 400);
        }

        wp_send_json_success([
            'message' => sprintf('%d fichier%s uploadé%s%s.', $uploaded, $uploaded > 1 ? 's' : '', $uploaded > 1 ? 's' : '', $skipped > 0 ? ' (' . $skipped . ' ignoré(s))' : ''),
        ]);
    }

    public function ajaxDelete(): void
    {
        $this->guardAjax();

        $dirKey = (isset($_POST['dir']) && $_POST['dir'] === 'multi') ? 'multi' : 'fr';
        $file = isset($_POST['file']) ? basename(sanitize_text_field((string) wp_unslash($_POST['file']))) : '';

        if ($file === '') {
            wp_send_json_error(['message' => 'Fichier invalide.'], 400);
        }

        $dir = $this->pathForDir($dirKey);
        $realDir = realpath($dir);
        $realFile = realpath(trailingslashit($dir) . $file);

        if ($realDir === false || $realFile === false || !str_starts_with($realFile, $realDir) || !is_file($realFile)) {
            wp_send_json_error(['message' => 'Fichier introuvable.'], 404);
        }

        if (!unlink($realFile)) {
            wp_send_json_error(['message' => 'Suppression impossible.'], 500);
        }

        wp_send_json_success(['message' => 'Fichier supprimé.']);
    }

    public function exportLogs(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.');
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce((string) $_GET['_wpnonce'], self::NONCE_ACTION)) {
            wp_die('Nonce invalide.');
        }

        $filters = [
            'search' => isset($_GET['search']) ? sanitize_text_field((string) wp_unslash($_GET['search'])) : '',
            'dir' => (isset($_GET['dir']) && in_array($_GET['dir'], ['fr', 'multi'], true)) ? (string) $_GET['dir'] : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field((string) wp_unslash($_GET['date_from'])) : '',
            'date_to' => isset($_GET['date_to']) ? sanitize_text_field((string) wp_unslash($_GET['date_to'])) : '',
            'limit' => 5000,
        ];

        $logs = Logs::getLogs($filters);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="fds-logs-' . gmdate('Y-m-d-His') . '.csv"');

        $output = fopen('php://output', 'w');

        if ($output === false) {
            exit;
        }

        fputcsv($output, ['Date', 'Utilisateur', 'Email', 'Fichier', 'Répertoire', 'IP']);

        foreach ($logs as $log) {
            $user = get_user_by('id', (int) $log['user_id']);
            fputcsv($output, [
                (string) $log['downloaded_at'],
                $user ? $user->display_name : 'Utilisateur #' . (string) $log['user_id'],
                $user ? $user->user_email : '',
                (string) $log['file_name'],
                (string) $log['dir_key'],
                (string) $log['ip_address'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * @param array<string, mixed> $source
     * @return array{dir:string,search:string,ext:string,date_from:string,date_to:string,per_page:int,page:int,sort:string,order:string}
     */
    private function getFileFilters(array $source): array
    {
        $sort = isset($source['sort']) ? sanitize_key((string) wp_unslash($source['sort'])) : 'date';
        $order = isset($source['order']) ? sanitize_key((string) wp_unslash($source['order'])) : 'desc';

        return [
            'dir' => (isset($source['dir']) && $source['dir'] === 'multi') ? 'multi' : 'fr',
            'search' => isset($source['search']) ? sanitize_text_field((string) wp_unslash($source['search'])) : '',
            'ext' => isset($source['ext']) ? sanitize_key((string) wp_unslash($source['ext'])) : '',
            'date_from' => isset($source['date_from']) ? sanitize_text_field((string) wp_unslash($source['date_from'])) : '',
            'date_to' => isset($source['date_to']) ? sanitize_text_field((string) wp_unslash($source['date_to'])) : '',
            'per_page' => min(100, max(1, (int) ($source['per_page'] ?? 10))),
            'page' => max(1, (int) ($source['page'] ?? 1)),
            'sort' => in_array($sort, ['name', 'ext', 'date', 'size'], true) ? $sort : 'date',
            'order' => $order === 'asc' ? 'asc' : 'desc',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @param array{search:string,ext:string,date_from:string,date_to:string} $filters
     * @return array<int, array<string, mixed>>
     */
    private function filterFiles(array $files, array $filters): array
    {
        $search = mb_strtolower($filters['search']);
        $from = $filters['date_from'] !== '' ? strtotime($filters['date_from'] . ' 00:00:00') : false;
        $to = $filters['date_to'] !== '' ? strtotime($filters['date_to'] . ' 23:59:59') : false;

        return array_values(array_filter($files, static function (array $file) use ($filters, $search, $from, $to): bool {
            if ($filters['ext'] !== '' && $file['ext'] !== $filters['ext']) {
                return false;
            }

            if ($search !== '' && !str_contains(mb_strtolower((string) $file['name'] . ' ' . (string) $file['label']), $search)) {
                return false;
            }

            if ($from !== false && (int) $file['timestamp'] < $from) {
                return false;
            }

            if ($to !== false && (int) $file['timestamp'] > $to) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $files
     */
    private function sortFiles(array &$files, string $sort, string $order): void
    {
        usort($files, static function (array $a, array $b) use ($sort, $order): int {
            $key = match ($sort) {
                'name' => 'label',
                'size' => 'size_bytes',
                'ext' => 'ext',
                default => 'timestamp',
            };

            $result = is_numeric($a[$key] ?? null)
                ? (int) $a[$key] <=> (int) $b[$key]
                : strnatcasecmp((string) ($a[$key] ?? ''), (string) ($b[$key] ?? ''));

            return $order === 'asc' ? $result : -$result;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $files
     */
    private function renderRows(array $files, string $dir): string
    {
        if (empty($files)) {
            return '<tr><td colspan="5">Aucun fichier ne correspond aux filtres.</td></tr>';
        }

        ob_start();

        foreach ($files as $file) {
            $link = wp_nonce_url(
                add_query_arg([
                    FEVDI_FDS_URL_PARAM => (string) $file['name'],
                    'dir' => $dir,
                ], site_url('/')),
                'fds_download_' . (string) $file['name']
            );
            ?>
            <tr>
                <td><span class="fds-file-type"><?php echo esc_html((string) $file['ext']); ?></span></td>
                <td>
                    <strong><?php echo esc_html((string) $file['label']); ?></strong>
                    <span class="fds-file-name"><?php echo esc_html((string) $file['name']); ?></span>
                </td>
                <td><?php echo esc_html((string) $file['size']); ?></td>
                <td><?php echo esc_html((string) $file['date']); ?></td>
                <td class="fds-admin-actions">
                    <a href="<?php echo esc_url($link); ?>" class="fds-admin-link">Télécharger</a>
                    <button type="button" class="fds-admin-danger" data-fds-delete="<?php echo esc_attr((string) $file['name']); ?>" data-dir="<?php echo esc_attr($dir); ?>">Supprimer</button>
                </td>
            </tr>
            <?php
        }

        return (string) ob_get_clean();
    }

    private function guardAjax(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Accès refusé.'], 403);
        }

        check_ajax_referer(self::NONCE_ACTION, 'nonce');
    }

    private function pathForDir(string $dir): string
    {
        return $dir === 'multi' ? FEVDI_FDS_PATH_MULTI : FEVDI_FDS_PATH_FR;
    }
}
