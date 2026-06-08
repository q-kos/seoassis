<?php

class PageFetcher
{
    private const MAX_HTML_BYTES = 150_000; // ~150 KB — достаточно для анализа, не превышаем токены

    /**
     * Скачивает HTML страницы по URL.
     * Если страница слишком большая — обрезает.
     */
    public function fetch(string $url): string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SEOAuditBot/1.0)',
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '', // поддержка gzip/deflate
        ]);

        $html       = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("Не удалось загрузить страницу: {$curlError}");
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException("Страница вернула HTTP {$statusCode}");
        }

        // Обрезаем, если слишком большой документ
        if (strlen($html) > self::MAX_HTML_BYTES) {
            $html = substr($html, 0, self::MAX_HTML_BYTES) . "\n<!-- [HTML обрезан для анализа] -->";
        }

        return $html;
    }
}
