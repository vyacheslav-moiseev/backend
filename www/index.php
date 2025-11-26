<?php
declare(strict_types=1);

// ==================== PROMETHEUS /metrics ====================
if ($_SERVER['REQUEST_URI'] === '/metrics' || $_SERVER['REQUEST_URI'] === '/metrics/') {
    $file = '/tmp/php_metrics.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [
        'requests_total' => 0,
        'request_duration_seconds' => [],
        'db_errors' => 0
    ];

    header('Content-Type: text/plain; version=0.0.4; charset=utf-8');

    // Основная метрика — будет расти!
    echo "# HELP http_requests_total Total HTTP requests processed by the application\n";
    echo "# TYPE http_requests_total counter\n";
    echo "http_requests_total " . ($data['requests_total'] ?? 0) . "\n\n";

    // Средняя длительность запросов
    $avg = 0;
    if (!empty($data['request_duration_seconds'])) {
        $avg = array_sum($data['request_duration_seconds']) / count($data['request_duration_seconds']);
    }
    echo "# HELP php_request_duration_seconds_average Average request duration\n";
    echo "# TYPE php_request_duration_seconds_average gauge\n";
    echo "php_request_duration_seconds_average " . round($avg, 4) . "\n\n";

    // DB ошибки
    echo "# HELP php_db_errors_total Total database errors\n";
    echo "# TYPE php_db_errors_total counter\n";
    echo "php_db_errors_total " . ($data['db_errors'] ?? 0) . "\n\n";

    // PHP версия
    echo "# HELP php_info PHP version information\n";
    echo "# TYPE php_info gauge\n";
    echo "php_info{version=\"" . PHP_VERSION . "\"} 1\n";

    exit;
}

// ==================== API ====================
header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=' . (getenv('DB_HOST') ?: 'mariadb') . ';dbname=' . (getenv('DB_NAME') ?: 'backend') . ';charset=utf8mb4',
        getenv('DB_USER') ?: 'user',
        getenv('DB_PASS') ?: 'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Инициализация метрик
$metrics_file = '/tmp/php_metrics.json';
if (!file_exists($metrics_file)) {
    file_put_contents($metrics_file, json_encode([
        'requests_total' => 0,
        'request_duration_seconds' => [],
        'db_errors' => 0
    ]));
}

function inc_metric(string $name, int $value = 1): void {
    global $metrics_file;
    $data = json_decode(file_get_contents($metrics_file), true) ?: [];
    $data[$name] = ($data[$name] ?? 0) + $value;
    file_put_contents($metrics_file, json_encode($data));
}

function observe_duration(float $duration): void {
    global $metrics_file;
    $data = json_decode(file_get_contents($metrics_file), true) ?: [];
    $data['request_duration_seconds'][] = $duration;
    if (count($data['request_duration_seconds']) > 1000) {
        array_shift($data['request_duration_seconds']);
    }
    file_put_contents($metrics_file, json_encode($data));
}

$start = microtime(true);
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

try {
    // === ТВОИ ЭНДПОИНТЫ — просто добавь в конец каждого блока:
    // inc_metric('requests_total');
    // observe_duration(microtime(true) - $start);
    // exit;

    if ($uri === '/users' || $uri === '/users/') {
        if ($method === 'GET') {
            $stmt = $pdo->query('SELECT id, name, email, created_at FROM users ORDER BY id DESC');
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
            $stmt->execute([$input['name'] ?? 'test', $input['email'] ?? 'test@example.com']);
            echo json_encode(['id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
        inc_metric('requests_total');
        observe_duration(microtime(true) - $start);
        exit;
    }

    // Добавь остальные эндпоинты (users/{id}, posts, posts/{id}) — и в конце каждого:
    // inc_metric('requests_total'); observe_duration(microtime(true) - $start); exit;

    http_response_code(404);
    echo json_encode(['error' => 'Not found']);

} catch (Throwable $e) {
    inc_metric('db_errors');
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}