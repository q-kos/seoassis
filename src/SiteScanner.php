<?php

class SiteScanner
{
    private string $baseHost;
    private string $baseScheme;
    private array  $visited  = [];
    private array  $queue    = [];
    private array  $results  = [];

    public function scan(string $startUrl): array
    {
        $parsed = parse_url($startUrl);
        if (!$parsed || empty($parsed['host'])) {
            throw new RuntimeException('Некорректный URL');
        }

        $this->baseHost   = $parsed['host'];
        $this->baseScheme = $parsed['scheme'] ?? 'https';
        $this->queue[]    = $this->normalizeUrl($startUrl);

        while (!empty($this->queue)) {
            $url = array_shift($this->queue);

            if (isset($this->visited[$url])) {
                continue;
            }
            $this->visited[$url] = true;

            $result = $this->fetchAndParse($url);
            $this->results[] = $result;

            if (!$result['error']) {
                foreach ($result['links'] as $link) {
                    if (!isset($this->visited[$link])) {
                        $this->queue[] = $link;
                    }
                }
            }

            unset($result['links']);
        }

        // strip internal links field from output
        foreach ($this->results as &$r) {
            unset($r['links']);
        }

        return $this->results;
    }

    private function fetchAndParse(string $url): array
    {
        $result = [
            'url'        => $url,
            'title'      => null,
            'title_len'  => 0,
            'desc'       => null,
            'desc_len'   => 0,
            'http_code'  => 0,
            'error'      => null,
            'links'      => [],
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; SEOScanBot/1.0)',
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => '',
        ]);

        $html      = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $result['http_code'] = $httpCode;

        if ($curlError) {
            $result['error'] = $curlError;
            return $result;
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            $result['error'] = "HTTP {$httpCode}";
            return $result;
        }

        // title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
            $result['title']     = $title;
            $result['title_len'] = mb_strlen($title);
        }

        // description
        if (preg_match('/<meta\s[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']/si', $html, $m)
         || preg_match('/<meta\s[^>]*content=["\'](.*?)["\']\s[^>]*name=["\']description["\']/si', $html, $m)) {
            $desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            $result['desc']     = $desc;
            $result['desc_len'] = mb_strlen($desc);
        }

        // internal links
        preg_match_all('/<a\s[^>]*href=["\']([^"\'#?][^"\']*)["\'][^>]*>/si', $html, $matches);
        foreach ($matches[1] as $href) {
            $abs = $this->toAbsolute($href, $url);
            if ($abs && $this->isSameDomain($abs)) {
                $normalized = $this->normalizeUrl($abs);
                if (!isset($this->visited[$normalized])) {
                    $result['links'][] = $normalized;
                }
            }
        }
        $result['links'] = array_unique($result['links']);

        return $result;
    }

    private function toAbsolute(string $href, string $base): ?string
    {
        if (preg_match('/^https?:\/\//i', $href)) {
            return $href;
        }
        if (str_starts_with($href, '//')) {
            return $this->baseScheme . ':' . $href;
        }
        if (str_starts_with($href, '/')) {
            return $this->baseScheme . '://' . $this->baseHost . $href;
        }
        // relative — build from base path
        $baseParsed = parse_url($base);
        $dir = isset($baseParsed['path']) ? dirname($baseParsed['path']) : '/';
        return $this->baseScheme . '://' . $this->baseHost . rtrim($dir, '/') . '/' . $href;
    }

    private function isSameDomain(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host === $this->baseHost;
    }

    private function normalizeUrl(string $url): string
    {
        // strip fragment, trailing slash on non-root paths
        $url = preg_replace('/#.*$/', '', $url);
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        if (strlen($path) > 1) {
            $path = rtrim($path, '/');
        }
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $path;
    }
}
