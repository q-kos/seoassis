<?php
require_once dirname(__DIR__) . '/ClaudeClient.php';
require_once dirname(__DIR__) . '/PageFetcher.php';

header('Content-Type: application/json; charset=utf-8');

$apiKey = getenv('ANTHROPIC_API_KEY') ?: (file_exists(dirname(__DIR__, 2) . '/.env') ? trim(parse_ini_file(dirname(__DIR__, 2) . '/.env')['ANTHROPIC_API_KEY'] ?? '') : '');

if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'API ключ не настроен. Укажи ANTHROPIC_API_KEY в переменной окружения или файле .env'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = trim($_POST['url'] ?? '');
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Укажи корректный URL'], JSON_UNESCAPED_UNICODE);
    exit;
}

$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array($scheme, ['http', 'https'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Поддерживаются только HTTP/HTTPS URL'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $fetcher = new PageFetcher();
    $html    = $fetcher->fetch($url);
    $claude  = new ClaudeClient($apiKey);
    $result  = $claude->analyzeSEO($html, $url);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
