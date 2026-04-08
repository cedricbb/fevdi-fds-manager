<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Product
{
    public function register(): void
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'fields']);
        add_action('woocommerce_process_product_meta', [$this, 'save']);
        add_action('woocommerce_single_product_summary', [$this, 'display'], 35);
    }

    public function fields(): void
    {
        global $post;

        if (!$post) {
            return;
        }

        $selectedFr = (string) get_post_meta($post->ID, '_fds_file_fr', true);
        $selectedMulti = (string) get_post_meta($post->ID, '_fds_file_multi', true);
        $filesFr = Scanner::getFileNames('fr');
        $filesMulti = Scanner::getFileNames('multi');

        echo '<div class="options_group">';

        echo '<p class="form-field"><label for="_fds_file_fr">FDS Français</label><select name="_fds_file_fr" id="_fds_file_fr" class="short">';
        echo '<option value="">— Auto —</option>';
        foreach ($filesFr as $file) {
            echo '<option value="' . esc_attr($file) . '" ' . selected($selectedFr, $file, false) . '>' . esc_html($file) . '</option>';
        }
        echo '</select></p>';

        echo '<p class="form-field"><label for="_fds_file_multi">FDS Multilingue</label><select name="_fds_file_multi" id="_fds_file_multi" class="short">';
        echo '<option value="">— Auto —</option>';
        foreach ($filesMulti as $file) {
            echo '<option value="' . esc_attr($file) . '" ' . selected($selectedMulti, $file, false) . '>' . esc_html($file) . '</option>';
        }
        echo '</select></p>';

        echo '</div>';
    }

    public function save(int $postId): void
    {
        if (isset($_POST['_fds_file_fr'])) {
            update_post_meta($postId, '_fds_file_fr', sanitize_text_field((string) wp_unslash($_POST['_fds_file_fr'])));
        }

        if (isset($_POST['_fds_file_multi'])) {
            update_post_meta($postId, '_fds_file_multi', sanitize_text_field((string) wp_unslash($_POST['_fds_file_multi'])));
        }
    }

    public function display(): void
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        global $product;
        if (!$product) {
            return;
        }

        $fileFr = (string) get_post_meta($product->get_id(), '_fds_file_fr', true);
        $fileMulti = (string) get_post_meta($product->get_id(), '_fds_file_multi', true);

        if ($fileFr === '') {
            $fileFr = Matcher::findBestMatch($product->get_id(), 'fr');
        }

        if ($fileMulti === '') {
            $fileMulti = Matcher::findBestMatch($product->get_id(), 'multi');
        }

        if ($fileFr === '' && $fileMulti === '') {
            return;
        }

        echo '<div class="fds-product-buttons">';

        if ($fileFr !== '') {
            $linkFr = wp_nonce_url(
                add_query_arg([
                    FEVDI_FDS_URL_PARAM => $fileFr,
                    'dir' => 'fr',
                ], site_url('/')),
                'fds_download_' . $fileFr
            );
            echo '<a class="button alt" href="' . esc_url($linkFr) . '">Télécharger la fiche sécurité</a>';
        }

        if ($fileMulti !== '') {
            $linkMulti = wp_nonce_url(
                add_query_arg([
                    FEVDI_FDS_URL_PARAM => $fileMulti,
                    'dir' => 'multi',
                ], site_url('/')),
                'fds_download_' . $fileMulti
            );
            echo '<a class="button" href="' . esc_url($linkMulti) . '">Télécharger la FDS multilingue</a>';
        }

        echo '</div>';
    }
}