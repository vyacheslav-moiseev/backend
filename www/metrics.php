<?php

require __DIR__ . '/../vendor/autoload.php';

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

$registry = new CollectorRegistry(new InMemory());

// === METRICS ===

// Счётчик всех запросов
$counter = $registry->getOrRegisterCounter(
    'app',
    'requests_total',
    'Total API requests',
    ['method', 'endpoint', 'status']
);

// Гистограмма времени ответа
$histogram = $registry->getOrRegisterHistogram(
    'app',
    'response_time_seconds',
    'Request duration',
    ['method', 'endpoint']
);

// Измерение времени запроса
$method = $_SERVER['REQUEST_METHOD'] ?? 'unknown';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Запускаем измерение
$start = microtime(true);

// ==== здесь подключается остальной API, MVC и т.п. ====
// но для metrics.php добавлять не нужно
// Prometheus сам выведет только метрики
// =======================================

// выставляем значения
$status = http_response_code();
$counter->inc([$method, $path, $status]);

$duration = microtime(true) - $start;
$histogram->observe($duration, [$method, $path]);

// отдаём метрики Prometheus
$renderer = new RenderTextFormat();
header('Content-Type: ' . RenderTextFormat::MIME_TYPE);
echo $renderer->render($registry->getMetricFamilySamples());
