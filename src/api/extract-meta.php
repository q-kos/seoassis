<?php
require_once dirname(__DIR__) . '/PageFetcher.php';
require_once dirname(__DIR__) . '/ClaudeClient.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_POST['action'] ?? 'extract';
$url    = trim($_POST['url'] ?? '');

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Укажи корректный URL'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $fetcher = new PageFetcher();
    $html    = $fetcher->fetch($url);

    // Extract title
    $title = '';
    if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
        $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
    }

    // Extract description
    $desc = '';
    if (preg_match('/<meta\s[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\']/si', $html, $m)
     || preg_match('/<meta\s[^>]*content=["\'](.*?)["\']\s+name=["\']description["\']/si', $html, $m)) {
        $desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    }

    $result = [
        'title'     => $title,
        'title_len' => mb_strlen($title),
        'desc'      => $desc,
        'desc_len'  => mb_strlen($desc),
    ];

    // If action=improve — ask Claude for suggestions
    if ($action === 'improve') {
        $apiKey = getenv('ANTHROPIC_API_KEY') ?: (file_exists(dirname(__DIR__, 2) . '/.env') ? trim(parse_ini_file(dirname(__DIR__, 2) . '/.env')['ANTHROPIC_API_KEY'] ?? '') : '');
        if (!$apiKey) {
            $result['ai_error'] = 'API ключ не настроен';
        } else {
            $claude = new ClaudeClient($apiKey);
            $suggestions = $claude->generateMeta($html, $url, $title, $desc);
            $result['suggestions'] = $suggestions;
        }
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
