<?php
// Simple PHP app with PDO + file-backed metrics for Prometheus scraping.
// Routes: /users, /users/{id}, /posts, /posts/{id}, /metrics

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST')?:'127.0.0.1', getenv('DB_NAME')?:'appdb');
$user = getenv('DB_USER')?:'appuser';
$pass = getenv('DB_PASS')?:'apppass';

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'DB connection failed', 'message'=>$e->getMessage()]);
    exit;
}

// metrics file
$metrics_file = '/tmp/php_metrics.json';
if (!file_exists($metrics_file)) {
    file_put_contents($metrics_file, json_encode(['requests_total'=>0,'request_duration_seconds'=>[], 'db_errors'=>0]));
}

function inc_metric($name, $value=1) {
    global $metrics_file;
    $data = json_decode(@file_get_contents($metrics_file), true);
    if (!$data) $data = [];
    if (!isset($data[$name])) $data[$name] = 0;
    $data[$name] += $value;
    file_put_contents($metrics_file, json_encode($data));
}

function observe_duration($val) {
    global $metrics_file;
    $data = json_decode(@file_get_contents($metrics_file), true);
    if (!$data) $data = [];
    if (!isset($data['request_duration_seconds'])) $data['request_duration_seconds'] = [];
    $data['request_duration_seconds'][] = $val;
    file_put_contents($metrics_file, json_encode($data));
}

$start = microtime(true);
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');

try {
    if ($uri === '/metrics') {
        // return prometheus plain text exposition
        $data = json_decode(@file_get_contents($metrics_file), true) ?: [];
        header('Content-Type: text/plain; version=0.0.4');
        $out = [];
        $out[] = '# HELP php_requests_total Total HTTP requests';
        $out[] = '# TYPE php_requests_total counter';
        $out[] = 'php_requests_total ' . ($data['requests_total'] ?? 0);
        $out[] = '# HELP php_request_duration_seconds Summary of request durations';
        $out[] = '# TYPE php_request_duration_seconds summary';
        $count = count($data['request_duration_seconds'] ?? []);
        $sum = array_sum($data['request_duration_seconds'] ?? []);
        $out[] = 'php_request_duration_seconds_count ' . $count;
        $out[] = 'php_request_duration_seconds_sum ' . $sum;
        $out[] = '# HELP php_db_errors_total Total DB errors';
        $out[] = '# TYPE php_db_errors_total counter';
        $out[] = 'php_db_errors_total ' . ($data['db_errors'] ?? 0);
        echo implode("\n", $out);
        exit;
    }

    // users endpoints
    if (preg_match('#^/users/?$#', $uri)) {
        if ($method === 'GET') {
            $stmt = $pdo->query('SELECT id, name, email, created_at FROM users');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
            $stmt->execute([$input['name'], $input['email']]);
            echo json_encode(['id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(405);
            echo json_encode(['error'=>'method not allowed']);
        }
        inc_metric('requests_total',1);
        $duration = microtime(true)-$start; observe_duration($duration);
        exit;
    }

    if (preg_match('#^/users/(\d+)$#', $uri, $m)) {
        $id = (int)$m[1];
        if ($method === 'GET') {
            $stmt = $pdo->prepare('SELECT id, name, email, created_at FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { http_response_code(404); echo json_encode(['error'=>'not found']); }
            else echo json_encode($row);
        } elseif ($method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare('UPDATE users SET name=?, email=? WHERE id=?');
            $stmt->execute([$input['name'], $input['email'], $id]);
            echo json_encode(['updated'=> $stmt->rowCount()]);
        } elseif ($method === 'DELETE') {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id=?');
            $stmt->execute([$id]);
            echo json_encode(['deleted'=> $stmt->rowCount()]);
        } else {
            http_response_code(405);
            echo json_encode(['error'=>'method not allowed']);
        }
        inc_metric('requests_total',1);
        $duration = microtime(true)-$start; observe_duration($duration);
        exit;
    }

    // posts endpoints
    if (preg_match('#^/posts/?$#', $uri)) {
        if ($method === 'GET') {
            $stmt = $pdo->query('SELECT id, user_id, title, body, created_at FROM posts');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($rows);
        } elseif ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, title, body) VALUES (?, ?, ?)');
            $stmt->execute([$input['user_id'], $input['title'], $input['body']]);
            echo json_encode(['id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(405);
            echo json_encode(['error'=>'method not allowed']);
        }
        inc_metric('requests_total',1);
        $duration = microtime(true)-$start; observe_duration($duration);
        exit;
    }

    if (preg_match('#^/posts/(\d+)$#', $uri, $m)) {
        $id = (int)$m[1];
        if ($method === 'GET') {
            $stmt = $pdo->prepare('SELECT id, user_id, title, body, created_at FROM posts WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) { http_response_code(404); echo json_encode(['error'=>'not found']); }
            else echo json_encode($row);
        } elseif ($method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare('UPDATE posts SET title=?, body=? WHERE id=?');
            $stmt->execute([$input['title'], $input['body'], $id]);
            echo json_encode(['updated'=> $stmt->rowCount()]);
        } elseif ($method === 'DELETE') {
            $stmt = $pdo->prepare('DELETE FROM posts WHERE id=?');
            $stmt->execute([$id]);
            echo json_encode(['deleted'=> $stmt->rowCount()]);
        } else {
            http_response_code(405);
            echo json_encode(['error'=>'method not allowed']);
        }
        inc_metric('requests_total',1);
        $duration = microtime(true)-$start; observe_duration($duration);
        exit;
    }

    // default
    http_response_code(404);
    echo json_encode(['error'=>'not found']);
} catch (Exception $e) {
    // increment db error metric
    $data = json_decode(@file_get_contents($metrics_file), true) ?: [];
    $data['db_errors'] = ($data['db_errors'] ?? 0) + 1;
    file_put_contents($metrics_file, json_encode($data));
    http_response_code(500);
    echo json_encode(['error'=>'exception','message'=>$e->getMessage()]);
}
