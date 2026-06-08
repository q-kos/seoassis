<?php
require_once dirname(__DIR__) . '/SiteScanner.php';

header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

$url = trim($_POST['url'] ?? '');
if (!$url) {
    $url = trim(file_get_contents('php://input'));
    $data = json_decode($url, true);
    $url = $data['url'] ?? '';
}

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Укажи корректный URL сайта'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $scanner = new SiteScanner();
    $pages   = $scanner->scan($url);

    $total      = count($pages);
    $noTitle    = 0;
    $noDesc     = 0;
    $shortTitle = 0;
    $longTitle  = 0;
    $shortDesc  = 0;
    $longDesc   = 0;
    $errors     = 0;

    foreach ($pages as $p) {
        if ($p['error']) { $errors++; continue; }
        if (!$p['title'])               $noTitle++;
        elseif ($p['title_len'] < 50)   $shortTitle++;
        elseif ($p['title_len'] > 70)   $longTitle++;
        if (!$p['desc'])                $noDesc++;
        elseif ($p['desc_len'] < 120)   $shortDesc++;
        elseif ($p['desc_len'] > 170)   $longDesc++;
    }

    echo json_encode([
        'pages' => $pages,
        'stats' => [
            'total'       => $total,
            'errors'      => $errors,
            'no_title'    => $noTitle,
            'short_title' => $shortTitle,
            'long_title'  => $longTitle,
            'no_desc'     => $noDesc,
            'short_desc'  => $shortDesc,
            'long_desc'   => $longDesc,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (RuntimeException $e) {
    http_response_code(502);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
