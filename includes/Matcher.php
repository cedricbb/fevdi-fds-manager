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

        $name = mb_strtolower($product->get_name());
        $sku = mb_strtolower((string) $product->get_sku());
        $files = Scanner::getFileNames($dir);

        foreach ($files as $file) {
            $fileLower = mb_strtolower($file);

            if ($sku !== '' && str_contains($fileLower, $sku)) {
                return $file;
            }

            if (str_contains($fileLower, $name)) {
                return $file;
            }
        }

        return '';
    }
}