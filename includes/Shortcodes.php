<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Shortcodes
{
    public function register(): void
    {
        add_shortcode('fds_list', [$this, 'renderList']);
    }

    public function renderList(array $atts = []): string
    {
        $atts = shortcode_atts([
                'dir' => 'fr',
                'per_page' => '10',
                'search' => 'true',
                'title' => '',
        ], $atts, 'fds_list');

        $dir = $atts['dir'] === 'multi' ? 'multi' : 'fr';
        $perPage = max(1, (int) $atts['per_page']);
        $showSearch = $atts['search'] === 'true';
        $title = (string) $atts['title'];
        $files = Scanner::getFiles($dir);

        ob_start();
        ?>
        <div class="fds-wrap" data-per-page="<?php echo esc_attr((string) $perPage); ?>">
            <?php if ($title !== '') : ?>
                <h2><?php echo esc_html($title); ?></h2>
            <?php endif; ?>

            <?php if ($showSearch) : ?>
                <input type="text" class="fds-search" placeholder="Rechercher une FDS...">
            <?php endif; ?>

            <table class="fds-table">
                <thead>
                <tr>
                    <th data-sort="ext" class="fds-sorttype">Type</th>
                    <th data-sort="name" class="fds-sortname">Nom</th>
                    <th data-sort="date" class="fds-sortdate">Date de modification</th>
                    <th class="fds-action">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($files as $file) :
                    $link = wp_nonce_url(
                            add_query_arg([
                                    FEVDI_FDS_URL_PARAM => $file['name'],
                                    'dir' => $dir,
                            ], site_url('/')),
                            'fds_download_' . $file['name']
                    );
                    ?>
                    <tr>
                        <td data-ext="<?php echo esc_attr($file['ext']); ?>"><?php echo esc_html($file['ext']); ?></td>
                        <td data-name="<?php echo esc_attr(mb_strtolower($file['label'])); ?>"><?php echo esc_html($file['label']); ?></td>
                        <td data-date="<?php echo esc_attr((string) $file['timestamp']); ?>"><?php echo esc_html($file['date']); ?></td>
                        <td><a href="<?php echo esc_url($link); ?>">Télécharger</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="100">
                            <div class="fds-pagination"></div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}