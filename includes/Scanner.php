<?php

declare(strict_types=1);

namespace Fevdi\FdsManager;

final class Scanner
{
    /**
     * @return array<int, array{name:string,label:string,size:string,size_bytes:int,date:string,timestamp:int,ext:string}>
     */
    public static function getFiles(string $dir): array
    {
        $path = $dir === 'multi' ? FEVDI_FDS_PATH_MULTI : FEVDI_FDS_PATH_FR;

        if (!file_exists($path) || !is_dir($path)) {
            return [];
        }

        $files = scandir($path);
        if ($files === false) {
            return [];
        }

        $result = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $full = $path . '/' . $file;

            if (is_file($full)) {
                $timestamp = filemtime($full) ?: time();
                $basename = (string) pathinfo($file, PATHINFO_FILENAME);
                $label = (string) preg_replace('/\s+(multi|[a-z]{2}-[a-z]{2}|[a-z]{4}|[a-z]{2})$/i', '', $basename);
                $result[] = [
                    'name'  => $file,
                    'label' => $label,
                    'size'  => size_format(filesize($full) ?: 0),
                    'size_bytes' => (int) (filesize($full) ?: 0),
                    'date'  => date_i18n('d/m/Y H:i', $timestamp),
                    'timestamp' => $timestamp,
                    'ext'   => strtolower((string) pathinfo($file, PATHINFO_EXTENSION)),
                ];
            }
        }

        usort($result, static fn(array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public static function getFileNames(string $dir): array
    {
        return array_map(
            static fn(array $file): string => $file['name'],
            self::getFiles($dir)
        );
    }
}
