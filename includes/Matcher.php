<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Matcher
{
    public static function findBestMatch(int $productId, string $dir = 'fr'): string
    {
        $product = wc_get_product($productId);

        if (!$product) {
            return '';
        }

        $name = self::normalize($product->get_name());
        $sku = self::normalize((string) $product->get_sku());
        $tokens = self::tokens($name);
        $files = Scanner::getFileNames($dir);
        $bestFile = '';
        $bestScore = 0.0;

        foreach ($files as $file) {
            $fileName = self::normalize((string) pathinfo($file, PATHINFO_FILENAME));

            if ($sku !== '' && str_contains($fileName, $sku)) {
                return $file;
            }

            if ($name !== '' && str_contains($fileName, $name)) {
                return $file;
            }

            $score = self::score($name, $tokens, $fileName);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestFile = $file;
            }
        }

        return $bestScore >= 0.62 ? $bestFile : '';
    }

    private static function normalize(string $value): string
    {
        $value = html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower($value);
        $converted = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;

        if (is_string($converted)) {
            $value = $converted;
        }

        $value = (string) preg_replace('/\b(fds|fiche|securite|securite|safety|data|sheet|pdf|fr|multi|multilingue)\b/i', ' ', $value);
        $value = (string) preg_replace('/[^a-z0-9]+/i', ' ', $value);

        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    /**
     * @return array<int, string>
     */
    private static function tokens(string $value): array
    {
        $parts = preg_split('/\s+/', $value) ?: [];

        return array_values(array_filter(
            array_unique($parts),
            static fn(string $token): bool => strlen($token) > 2
        ));
    }

    /**
     * @param array<int, string> $tokens
     */
    private static function score(string $name, array $tokens, string $fileName): float
    {
        if ($name === '' || $fileName === '') {
            return 0.0;
        }

        similar_text($name, $fileName, $similarity);
        $score = $similarity / 100;

        if (!empty($tokens)) {
            $hits = 0;

            foreach ($tokens as $token) {
                if (str_contains($fileName, $token)) {
                    $hits++;
                }
            }

            $score = max($score, $hits / count($tokens));
        }

        $distance = levenshtein(substr($name, 0, 255), substr($fileName, 0, 255));
        $maxLength = max(strlen($name), strlen($fileName), 1);
        $score = max($score, 1 - min(1, $distance / $maxLength));

        return $score;
    }
}
